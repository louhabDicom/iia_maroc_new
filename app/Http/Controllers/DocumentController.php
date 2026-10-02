<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DownloadGrant;
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
    public function download(Request $request, Document $document): BinaryFileResponse
    {
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
    public function presentation(Request $request, PresentationFile $file): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);

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
     * Only published documents, for the edition they belong to.
     */
    private function authorizeDocument(Document $document): void
    {
        abort_unless($document->is_published, 404);

        abort_unless(
            $document->edition_id === \App\Models\Edition::current()?->getKey(),
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