@extends('layouts.admin')

@section('title', __('admin.enquiries.title'))

@section('heading', __('admin.enquiries.title'))

@section('content')

    <p class="admin-meta mb-4">@lang('admin.enquiries.lede')</p>

    <form method="GET" action="{{ url()->current() }}" class="admin-form mb-4">
        <div class="admin-field">
            <label for="status">@lang('admin.enquiries.status')</label>
            <select name="status" id="status" class="admin-select">
                <option value="">@lang('admin.queue.enquiries')</option>
                @foreach ($stages as $option)
                    <option value="{{ $option }}" @selected($status === $option)>
                        @lang("admin.stages.enquiries.{$option}")
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">@lang('admin.orders.index.filter')</button>

        @if ($status !== null)
            <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline-light">
                @lang('admin.orders.index.all')
            </a>
        @endif
    </form>

    @if ($enquiries->isEmpty())
        <div class="admin-card">
            <p class="admin-empty mb-0">@lang('admin.enquiries.empty')</p>
        </div>
    @else
        <div class="d-flex flex-column gap-3">

            {{-- Cards, not a table, for the same reason as submissions: the
                 prospect's own message is the content, and it has to be readable
                 next to the stage control without a nested scrollbar. --}}
            @foreach ($enquiries as $enquiry)
                <article class="admin-card">
                    <div class="admin-card__head">
                        <div>
                            <h2 class="admin-card__title">{{ $enquiry->company }}</h2>
                            <span class="admin-meta">
                                {{ $enquiry->contact_name }} &middot;
                                <a href="mailto:{{ $enquiry->contact_email }}"
                                   class="admin-num text-decoration-none">
                                    {{ $enquiry->contact_email }}
                                </a>
                                @if ($enquiry->contact_phone)
                                    &middot; <span class="admin-num">{{ $enquiry->contact_phone }}</span>
                                @endif
                            </span>
                        </div>

                        <span class="admin-pill admin-pill--{{ $enquiry->colour() }}">
                            @lang("admin.stages.enquiries.{$enquiry->status}")
                        </span>
                    </div>

                    <div class="admin-card__body">
                        <dl class="row mb-3">
                            <dt class="col-sm-3 admin-meta">@lang('admin.enquiries.package')</dt>
                            <dd class="col-sm-9">
                                {{-- The tier name is a TranslatedString and the
                                     price is *not* read from the package: a tier
                                     can be repriced after the enquiry arrived, and
                                     the operator needs to see what the prospect
                                     was told, not today's figure. --}}
                                {{ $enquiry->package?->name ?? __('admin.enquiries.no_package') }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.enquiries.received')</dt>
                            <dd class="col-sm-9 admin-num">
                                {{ $enquiry->created_at->translatedFormat('d/m/Y H:i') }}
                            </dd>

                            <dt class="col-sm-3 admin-meta">@lang('admin.enquiries.handler')</dt>
                            <dd class="col-sm-9">
                                {{ $enquiry->handler?->displayName() ?? __('admin.enquiries.unassigned') }}
                            </dd>
                        </dl>

                        @if ($enquiry->message)
                            <div class="admin-prose">{{ $enquiry->message }}</div>
                        @endif

                        {{-- `website_url` is operator-supplied data from a public
                             form, so it is not turned into a live link without
                             validating the scheme — otherwise a stored
                             `javascript:` URL becomes a clickable script
                             execution in the one screen only staff can see. --}}
                        @if ($enquiry->website_url)
                            <p class="admin-meta mt-3 mb-0">
                                @lang('admin.enquiries.company') :
                                <span class="admin-num">{{ $enquiry->website_url }}</span>
                            </p>
                        @endif

                        @if ($enquiry->internal_notes)
                            <div class="ux-notice ux-notice--warning mt-3" role="note">
                                <i class="fas fa-note-sticky ux-notice__icon" aria-hidden="true"></i>
                                <div>{{ $enquiry->internal_notes }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="admin-card__body border-top" style="border-color: rgba(255,255,255,.07) !important;">
                        <form method="POST"
                              action="{{ route('admin.enquiries.status', $enquiry) }}"
                              class="admin-form">
                            @csrf

                            <div class="admin-field">
                                <label for="status-{{ $enquiry->id }}">@lang('admin.enquiries.stage')</label>
                                <select name="status" id="status-{{ $enquiry->id }}" class="admin-select">
                                    @foreach ($stages as $option)
                                        <option value="{{ $option }}" @selected($enquiry->status === $option)>
                                            @lang("admin.stages.enquiries.{$option}")
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="admin-field flex-grow-1" style="min-width: 16rem;">
                                <label for="notes-{{ $enquiry->id }}">@lang('admin.enquiries.notes')</label>
                                <textarea name="notes" id="notes-{{ $enquiry->id }}"
                                          class="admin-textarea"></textarea>
                                @error('notes')
                                    <span class="ux-notice ux-notice--danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">@lang('admin.enquiries.stage')</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($enquiries->hasPages())
            <div class="mt-3">{{ $enquiries->links() }}</div>
        @endif
    @endif

@endsection