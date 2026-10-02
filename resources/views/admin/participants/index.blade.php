@extends('layouts.admin')

@section('title', __('admin.participants.title'))

@section('heading', __('admin.participants.title'))

@section('content')

    <p class="admin-meta mb-4">@lang('admin.participants.lede')</p>

    {{-- A GET form, because this screen is used by typing a name and pressing
         Enter. It also means the search is bookmarkable and survives a reload,
         which matters when somebody hands the desk laptop to a colleague. --}}
    <form method="GET" action="{{ url()->current() }}" class="admin-form mb-4">
        <div class="admin-field flex-grow-1" style="min-width: 18rem;">
            <label for="q">@lang('admin.participants.search')</label>
            {{-- `inputmode="search"` and `autofocus`: on the morning of the
                 conference this page is opened and then typed into, so the field
                 should already hold the cursor. Autofocus is harmless here and
                 is not used anywhere on the public site, where it would fight
                 the reader mid-article. --}}
            <input type="search"
                   name="q"
                   id="q"
                   value="{{ $q }}"
                   class="admin-input"
                   placeholder="{{ __('admin.participants.placeholder') }}"
                   inputmode="search"
                   autocomplete="off"
                   autofocus>
            <span class="admin-meta">@lang('admin.participants.search_hint')</span>
        </div>

        <button type="submit" class="btn btn-primary">@lang('admin.participants.search')</button>

        @if ($q !== '')
            <a href="{{ route('admin.participants.index') }}" class="btn btn-outline-light">
                @lang('admin.orders.index.all')
            </a>
        @endif
    </form>

    <div class="admin-card">
        <div class="admin-card__head">
            <h2 class="admin-card__title">
                @lang('admin.participants.total') : <span class="admin-num">{{ $participants->total() }}</span>
            </h2>
        </div>

        <div class="admin-card__body admin-card__body--flush">
            @if ($participants->isEmpty())
                <p class="admin-empty mb-0">@lang('admin.participants.empty')</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <caption class="visually-hidden">@lang('admin.participants.title')</caption>
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.participants.name')</th>
                                <th scope="col">@lang('admin.participants.badge')</th>
                                <th scope="col">@lang('admin.orders.show.contact')</th>
                                <th scope="col">@lang('admin.participants.job_title')</th>
                                <th scope="col">@lang('admin.participants.status')</th>
                                <th scope="col">
                                    <span class="visually-hidden">@lang('admin.participants.check_in')</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participants as $participant)
                                <tr>
                                    <td>
                                        {{ $participant->full_name }}
                                        @if ($participant->is_member)
                                            {{-- Member places are allocated at the
                                                 member rate, so the badge on the
                                                 desk has to say so: a delegate
                                                 queued at the wrong rate is a
                                                 conversation at the worst moment. --}}
                                            <span class="admin-pill admin-pill--info">
                                                @lang('admin.participants.member')
                                            </span>
                                        @endif
                                    </td>

                                    <td>{{ $participant->badgeLabel() }}</td>

                                    <td class="admin-table__wrap-cell">
                                        <span class="admin-num">{{ $participant->email }}</span>
                                        @if ($participant->phone)
                                            <span class="admin-num d-block">{{ $participant->phone }}</span>
                                        @endif
                                    </td>

                                    <td>{{ $participant->job_title ?: '—' }}</td>

                                    <td>
                                        @if ($participant->checked_in)
                                            <span class="admin-pill admin-pill--success">
                                                @lang('admin.participants.checked_in')
                                            </span>
                                        @else
                                            <span class="admin-pill admin-pill--muted">
                                                @lang('admin.participants.not_checked_in')
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{-- Two separate buttons, not a toggle. At
                                             the door a mis-tap has to be correctable
                                             in one further press, and a toggle
                                             fired twice would silently un-arrive
                                             somebody who is standing right there. --}}
                                        @if ($participant->checked_in)
                                            <form method="POST"
                                                  action="{{ route('admin.participants.check-out', $participant) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-light">
                                                    @lang('admin.participants.check_out')
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST"
                                                  action="{{ route('admin.participants.check-in', $participant) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    @lang('admin.participants.check_in')
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($participants->hasPages())
                    <div class="p-3 border-top" style="border-color: rgba(255,255,255,.07) !important;">
                        {{ $participants->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

@endsection