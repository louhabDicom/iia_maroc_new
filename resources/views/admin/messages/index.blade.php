@extends('layouts.admin')

@section('title', __('admin.messages.title'))

@section('heading', __('admin.messages.title'))

@section('content')

    {{-- Stated before the list, not in a tooltip. The button below is the one
         place in the product where "answer" does not mean "send", and an
         operator who finds that out after writing a reply has wasted the work. --}}
    <p class="admin-meta mb-4">@lang('admin.messages.lede')</p>

    <form method="GET" action="{{ url()->current() }}" class="admin-form mb-4">
        <div class="admin-field">
            <label for="status">@lang('admin.messages.status')</label>
            <select name="status" id="status" class="admin-select">
                <option value="">@lang('admin.queue.messages')</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option }}" @selected($status === $option)>
                        @lang("admin.stages.messages.{$option}")
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">@lang('admin.orders.index.filter')</button>

        @if ($status !== null)
            <a href="{{ route('admin.messages.index') }}" class="btn btn-outline-light">
                @lang('admin.orders.index.all')
            </a>
        @endif
    </form>

    @if ($messages->isEmpty())
        <div class="admin-card">
            <p class="admin-empty mb-0">@lang('admin.messages.empty')</p>
        </div>
    @else
        <div class="d-flex flex-column gap-3">

            @foreach ($messages as $contact)
                <article class="admin-card">
                    <div class="admin-card__head">
                        <div>
                            <h2 class="admin-card__title">
                                {{ $contact->subject_type
                                    ? __('admin.stages.subjects.'.$contact->subject_type)
                                    : __('admin.stages.subjects.other') }}
                                &middot; <span class="fw-normal">{{ $contact->name }}</span>
                            </h2>
                            <span class="admin-meta">
                                <a href="mailto:{{ $contact->email }}" class="admin-num text-decoration-none">
                                    {{ $contact->email }}
                                </a>
                                @if ($contact->phone)
                                    &middot; <span class="admin-num">{{ $contact->phone }}</span>
                                @endif
                            </span>
                        </div>

                        <span class="admin-pill admin-pill--{{ $contact->colour() }}">
                            @lang("admin.stages.messages.{$contact->status}")
                        </span>
                    </div>

                    <div class="admin-card__body">
                        <dl class="row mb-3">
                            {{-- The visitor wrote in one of the three site
                                 languages, and it is often not the locale the
                                 operator is working in. Saying which one it was
                                 stops a reply going out in the wrong language
                                 with nothing on screen to prompt the check. --}}
                            <dt class="col-sm-3 admin-meta">@lang('admin.messages.wrote_in')</dt>
                            <dd class="col-sm-9">
                                {{ strtoupper((string) $contact->locale) }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.messages.received')</dt>
                            <dd class="col-sm-9 admin-num">
                                {{ $contact->created_at->translatedFormat('d/m/Y H:i') }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.messages.handler')</dt>
                            <dd class="col-sm-9">
                                {{ $contact->handler?->displayName() ?? __('admin.messages.unassigned') }}
                            </dd>
                        </dl>

                        <div class="admin-prose">{{ $contact->message }}</div>

                        @if ($contact->internal_notes)
                            <div class="ux-notice ux-notice--warning mt-3" role="note">
                                <i class="fas fa-note-sticky ux-notice__icon" aria-hidden="true"></i>
                                <div>{{ $contact->internal_notes }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="admin-card__body border-top" style="border-color: rgba(255,255,255,.07) !important;">
                        <form method="POST"
                              action="{{ route('admin.messages.status', $contact) }}"
                              class="admin-form">
                            @csrf

                            <div class="admin-field">
                                <label for="status-{{ $contact->id }}">@lang('admin.messages.status')</label>
                                <select name="status" id="status-{{ $contact->id }}" class="admin-select">
                                    @foreach ($statuses as $option)
                                        <option value="{{ $option }}" @selected($contact->status === $option)>
                                            @lang("admin.stages.messages.{$option}")
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="admin-field flex-grow-1" style="min-width: 16rem;">
                                <label for="notes-{{ $contact->id }}">@lang('admin.messages.notes')</label>
                                <textarea name="notes" id="notes-{{ $contact->id }}"
                                          class="admin-textarea"></textarea>
                                @error('notes')
                                    <span class="ux-notice ux-notice--danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                @lang('admin.messages.answer')
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($messages->hasPages())
            <div class="mt-3">{{ $messages->links() }}</div>
        @endif
    @endif

@endsection