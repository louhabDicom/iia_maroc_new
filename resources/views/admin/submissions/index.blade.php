@extends('layouts.admin')

@section('title', __('admin.submissions.title'))

@section('heading', __('admin.submissions.title'))

@section('content')

    <p class="admin-meta mb-4">@lang('admin.submissions.lede')</p>

    <form method="GET" action="{{ url()->current() }}" class="admin-form mb-4">
        <div class="admin-field">
            <label for="status">@lang('admin.submissions.status')</label>
            <select name="status" id="status" class="admin-select">
                {{-- An empty value means "the pending queue", which is the
                     controller's default when no status is given. The option is
                     labelled as such rather than "all", because the unfiltered
                     listing is genuinely narrower than every status. --}}
                <option value="">@lang('admin.queue.submissions')</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option }}" @selected($status === $option)>
                        @lang("admin.stages.submissions.{$option}")
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">@lang('admin.orders.index.filter')</button>

        @if ($status !== null)
            <a href="{{ route('admin.submissions.index') }}" class="btn btn-outline-light">
                @lang('admin.orders.index.all')
            </a>
        @endif
    </form>

    @if ($submissions->isEmpty())
        <div class="admin-card">
            <p class="admin-empty mb-0">@lang('admin.submissions.empty')</p>
        </div>
    @else
        <div class="d-flex flex-column gap-3">

            {{-- One card per submission rather than a table: a proposal is
                 several hundred words of abstract, and a table cell cannot hold
                 that without becoming a wall of truncated text. The decision form
                 is inline so a reviewer never loses their place scrolling back
                 up to the abstract. --}}
            @foreach ($submissions as $submission)
                <article class="admin-card">
                    <div class="admin-card__head">
                        <div>
                            <h2 class="admin-card__title">{{ $submission->full_name }}</h2>
                            @if ($submission->organisation || $submission->job_title)
                                <span class="admin-meta">
                                    {{ collect([$submission->job_title, $submission->organisation])->filter()->join(' · ') }}
                                </span>
                            @endif
                        </div>

                        <span class="admin-pill admin-pill--{{ $submission->colour() }}">
                            @lang("admin.stages.submissions.{$submission->status}")
                        </span>
                    </div>

                    <div class="admin-card__body">
                        <dl class="row mb-3">
                            <dt class="col-sm-3 admin-meta">@lang('admin.submissions.submitted_on')</dt>
                            <dd class="col-sm-9 admin-num">
                                {{ $submission->created_at->translatedFormat('d/m/Y H:i') }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.submissions.track')</dt>
                            <dd class="col-sm-9">
                                {{ $submission->requestedTrack?->name ?? __('admin.submissions.any_track') }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.orders.show.contact')</dt>
                            <dd class="col-sm-9">
                                {{ $submission->email }}
                                @if ($submission->phone)
                                    <span class="admin-num d-block">{{ $submission->phone }}</span>
                                @endif
                            </dd>
                        </dl>

                        <h3 class="fw-bold">{{ $submission->session_title }}</h3>

                        {{-- `admin-prose` carries `white-space: pre-wrap`, so a
                             submitted outline keeps its own paragraph breaks
                             instead of collapsing into one paragraph. --}}
                        <div class="admin-prose">{{ $submission->abstract }}</div>

                        @if ($submission->outline)
                            <details class="mt-3">
                                <summary class="fw-bold">@lang('admin.submissions.outline')</summary>
                                <div class="admin-prose mt-2">{{ $submission->outline }}</div>
                            </details>
                        @endif

                        @if ($submission->review_notes)
                            <div class="ux-notice ux-notice--warning mt-3" role="note">
                                <i class="fas fa-note-sticky ux-notice__icon" aria-hidden="true"></i>
                                <span>{{ $submission->review_notes }}</span>
                            </div>
                        @endif

                        @if ($submission->reviewer)
                            <p class="admin-meta mt-2 mb-0">
                                {{ $submission->reviewer->displayName() }}
                                @if ($submission->reviewed_at)
                                    &middot; <span class="admin-num">{{ $submission->reviewed_at->translatedFormat('d/m/Y H:i') }}</span>
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="admin-card__body border-top" style="border-color: rgba(255,255,255,.07) !important;">
                        <form method="POST"
                              action="{{ route('admin.submissions.review', $submission) }}"
                              class="admin-form">
                            @csrf

                            <div class="admin-field">
                                <label for="status-{{ $submission->id }}">@lang('admin.submissions.status')</label>
                                <select name="status" id="status-{{ $submission->id }}" class="admin-select">
                                    @foreach ($statuses as $option)
                                        <option value="{{ $option }}" @selected($submission->status === $option)>
                                            @lang("admin.stages.submissions.{$option}")
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="admin-field flex-grow-1" style="min-width: 16rem;">
                                <label for="notes-{{ $submission->id }}">@lang('admin.submissions.notes')</label>
                                <textarea name="review_notes" id="notes-{{ $submission->id }}"
                                          class="admin-textarea">{{ old("review_notes") }}</textarea>
                                @error('review_notes')
                                    <span class="ux-notice ux-notice--danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">@lang('admin.submissions.review')</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($submissions->hasPages())
            <div class="mt-3">{{ $submissions->links() }}</div>
        @endif
    @endif

@endsection