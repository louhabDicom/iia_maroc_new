<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DownloadGrant;
use App\Models\Edition;
use App\Models\PresentationFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Document downloads.
 *
 * Exists because `Document::downloadUrl()` has always pointed at
 * `route('documents.download')`, which was never registered — calling it
 * threw a RouteNotFoundException. Any page rendering a document list would
 * have been a 500.
 *
 * Files live on a non-public disk and are streamed through this route. A
 * public URL would make every document reachable by guessing a path, and the
 * 2024 site served its technical sheet and programme straight out of
 * `assets/` where anyone could read the directory.
 */
class DocumentController extends Controller
{
    /**
     * A published document for the current edition.
     */
    public function download(Request $request): BinaryFileResponse
    {
        // Resolved by name from the route rather than bound to the signature.
        // The URI is `{locale?}/documents/{document}/download`, and action
        // arguments are filled positionally, so `/fr/documents/12/download`
        // handed `$document` the string 'fr' — which bound to nothing and threw.
        // The download worked in Arabic and was broken in French and English.
        $document = Document::query()
            ->whereKey($this->routeId($request, 'document'))
            ->firstOrFail();

        $this->authorizeDocument($document);

        $path = (string) $document->file_path;

        abort_unless(Storage::disk($this->disk())->exists($path), 404);

        return Storage::disk($this->disk())->download(
            $path,
            // The stored name is not a safe download name: it can carry a
            // path separator or a header injection attempt from an admin
            // upload, and it is shown to the customer in a browser.
            $this->safeName((string) $document->file_name, $path),
        );
    }

    /**
     * A speaker's slides.
     *
     * Gated on a live DownloadGrant, not on a hardcoded access code. The 2024
     * `validate_access_code.php` compared a posted field against
     * `IIAConference#2024@`, a secret committed to the repository and printed
     * in the page source, guarding every delegate's slides.
     */
    public function presentation(Request $request): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);

        // By name, for the same positional reason as download() above: the URI
        // is `{locale?}/presentations/{file}/download`.
        $file = PresentationFile::query()
            ->whereKey($this->routeId($request, 'file'))
            ->firstOrFail();

        $grant = $user->downloadGrants()
            ->active()
            ->get()
            ->first(fn ($grant): bool => $grant->covers(DownloadGrant::SCOPE_PRESENTATIONS));

        abort_if($grant === null, 403);

        // Refused past the limit, and the refusal is enforced in the UPDATE's
        // WHERE clause so two concurrent downloads cannot both take the last
        // one.
        abort_unless($grant->recordDownload(), 403);

        $path = (string) $file->file_path;

        abort_unless($file->is_published, 404);
        abort_unless(Storage::disk($this->disk())->exists($path), 404);

        return Storage::disk($this->disk())->download(
            $path,
            $this->safeName((string) $file->title, $path, 'pdf'),
        );
    }

    // --- Helpers ---------------------------------------------------------

    /**
     * The value of a named route parameter.
     *
     * Every public route on this site leads with an optional `{locale?}`, and
     * a controller action's arguments are filled positionally from the route's
     * parameters. So `/fr/documents/12/download` arrives as ['fr', '12'], and a
     * typed `Document $document` argument is handed 'fr'. The failure is silent
     * — it looks like a missing record rather than a wiring mistake — and it
     * only affects the language-prefixed URLs, which is why it survives in a
     * site whose default locale happens to be the unprefixed one.
     *
     * Reading by name removes the coupling entirely.
     */
    private function routeId(Request $request, string $parameter): int
    {
        $value = $request->route($parameter);

        abort_if($value === null, 404);

        return (int) $value;
    }

    /**
     * Only published documents, for the edition they belong to.
     */
    private function authorizeDocument(Document $document): void
    {
        abort_unless($document->is_published, 404);

        abort_unless(
            $document->edition_id === Edition::current()?->getKey(),
            404,
        );
    }

    private function disk(): string
    {
        return 'local';
    }

    /**
     * A download filename that cannot escape the directory or inject headers.
     */
    private function safeName(?string $preferred, string $path, string $fallbackExtension = 'pdf'): string
    {
        $name = basename((string) $preferred);

        if ($name === '' || $name === '.' || $name === '..') {
            $name = pathinfo(basename($path), PATHINFO_FILENAME);
        }

        $name = (string) preg_replace('/[^A-Za-z0-9._-]/u', '-', $name);
        $name = trim($name, '.-');

        if ($name === '') {
            $name = 'document';
        }

        return str_contains($name, '.') ? $name : $name.'.'.$fallbackExtension;
    }
}
