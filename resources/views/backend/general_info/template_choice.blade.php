@extends('backend.master')

@section('header_css')
    <style>
        .tc-tabs .nav-link {
            cursor: pointer;
        }

        .tc-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 14px;
            overflow: hidden;
            transition: all .15s ease-in-out;
            background: #fff;
        }

        .tc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, .08);
        }

        .tc-card.selected {
            border-color: rgba(79, 70, 229, .55);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
        }

        .tc-card img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            background: #f8fafc;
        }

        .tc-card .tc-card-body {
            padding: 14px 14px 10px 14px;
        }

        .tc-card .tc-radio {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .tc-muted {
            color: #64748b;
            font-size: 12px;
        }
    </style>
@endsection

@section('page_title')
    Website Config
@endsection
@section('page_heading')
    Template Choice
@endsection

@section('content')
    <div class="wt_wrap">

        <!-- Page Header -->
        <div class="wt_header">
            <div class="wt_header-left">
                <div class="wt_header-icon"><i class="fas fa-palette"></i></div>
                <div>
                    <h4>Template Choice</h4>
                    <span>Choose a template for your website</span>
                </div>
            </div>
        </div>

        @php
            $tabs = [
                'banner_template' => 'Banner',
                'product_card_template' => 'Product Card',
                'product_details_template' => 'Product Details',
                'blog_card_template' => 'Blog Card',
                'header_template' => 'Header',
                'footer_template' => 'Footer',
                'landing_page_version' => 'Landing Page',
                'invoice_version' => 'Invoice Page',
                'featured_category_product' => 'Featured Category Products',
                'main_menu_template' => 'Main Menu Template',
            ];
            $current = [
                'banner_template' => $data->banner_template ?? 'v1',
                'product_card_template' => $data->product_card_template ?? 'v1',
                'product_details_template' => $data->product_details_template ?? 'v1',
                'blog_card_template' => $data->blog_card_template ?? 'v1',
                'header_template' => $data->header_template ?? 'v1',
                'footer_template' => $data->footer_template ?? 'v1',
                'landing_page_version' => $data->landing_page_version ?? 'v1',
                'invoice_version' => $data->invoice_version ?? 'v1',
                'featured_category_product' => $data->featured_category_product ?? 'v2',
                'main_menu_template' => $data->main_menu_template ?? 'v2',
            ];
            $contents = [
                'banner_template' => [
                    'v1' => 'assets/images/templates/banner_v1.png',
                    'v2' => 'assets/images/templates/banner_v2.png',
                    'v3' => 'assets/images/templates/banner_v3.png',
                ],
                'product_card_template' => [
                    'v1' => 'assets/images/templates/product_v1.png',
                    'v2' => 'assets/images/templates/product_v2.png',
                    'v3' => 'assets/images/templates/product_v3.png',
                ],
                'product_details_template' => [
                    'v1' => 'assets/images/templates/product_deatils_v1.png',
                    'v2' => 'assets/images/templates/product_deatils_v2.png',
                ],
                'blog_card_template' => [
                    'v1' => 'assets/images/templates/blog_v1.png',
                ],
                'header_template' => [
                    'v1' => 'assets/images/templates/header_v1.png',
                ],
                'footer_template' => [
                    'v1' => 'assets/images/templates/footer_v1.png',
                    'v2' => 'assets/images/templates/footer_v2.png',
                ],
                'landing_page_version' => [
                    'v1' => 'assets/images/templates/landing_v1.png',
                    'v2' => 'assets/images/templates/landing_v2.png',
                ],
                'invoice_version' => [
                    'v1' => 'assets/images/templates/invoice_v1.png',
                    'v2' => 'assets/images/templates/invoice_v2.png',
                ],
                'featured_category_product' => [
                    'v1' => 'assets/images/templates/featured_category_product_v1.png',
                    'v2' => 'assets/images/templates/featured_category_product_v2.png',
                ],
                'main_menu_template' => [
                    'v1' => 'assets/images/templates/main_menu_template_v1.png',
                    'v2' => 'assets/images/templates/main_menu_template_v2.png',
                ],
            ];

            $col_sizes = [
                'banner_template' => 4,
                'product_card_template' => 3,
                'product_details_template' => 4,
                'blog_card_template' => 3,
                'header_template' => 12,
                'footer_template' => 6,
                'landing_page_version' => 4,
                'invoice_version' => 4,
                'featured_category_product' => 4,
                'main_menu_template' => 6,
            ];

            // Fallback previews (in case referenced png doesn't exist in public/)
$versionPreviewFallback = [
    'v1' => versioned_url('assets/images/template-preview-v1.svg'),
    'v2' => versioned_url('assets/images/template-preview-v2.svg'),
            ];
        @endphp

        <div class="card mt-3">
            <div class="card-body">
                <ul class="nav nav-tabs tc-tabs" role="tablist">
                    @foreach ($tabs as $col => $label)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $col }}"
                                data-toggle="tab" href="#pane-{{ $col }}" role="tab"
                                aria-controls="pane-{{ $col }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content pt-3">
                    @foreach ($tabs as $col => $label)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pane-{{ $col }}"
                            role="tabpanel" aria-labelledby="tab-{{ $col }}">

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <h5 class="mb-0">{{ $label }} Template</h5>
                                    <div class="tc-muted">Select one version. Changing will update instantly.</div>
                                </div>
                                <div class="badge badge-soft-primary">Current: <span class="tc-current"
                                        data-column="{{ $col }}">{{ $current[$col] }}</span></div>
                            </div>

                            <div class="row mt-3">
                                @php
                                    $versions = $contents[$col] ?? [];
                                    $colSize = $col_sizes[$col] ?? (count($versions) > 1 ? 6 : 12);
                                @endphp

                                @foreach ($versions as $ver => $imgPath)
                                    @php
                                        $imgUrl =
                                            $versionPreviewFallback[$ver] ?? ($versionPreviewFallback['v1'] ?? '');

                                        if ($imgPath && file_exists(public_path($imgPath))) {
                                            $imgUrl = versioned_url($imgPath);
                                        }

                                        $selectedVersion = $current[$col] ?? 'v1';
                                        $isSelected = $selectedVersion === $ver;
                                    @endphp

                                    <div class="col-lg-{{ $colSize }} mb-3">
                                        <label class="w-100 mb-0 h-100">
                                            <div class="tc-card tc-option {{ $isSelected ? 'selected' : '' }}"
                                                data-column="{{ $col }}" data-version="{{ $ver }}">
                                                <img src="{{ $imgUrl }}" alt="{{ $ver }} preview"
                                                    style="width: 100%; height: 100%; object-fit: contain;">
                                                <div class="tc-card-body">
                                                    <div class="tc-radio">
                                                        <div>
                                                            <strong>Version {{ $ver }}</strong>
                                                            <div class="tc-muted">
                                                                {{ $isSelected ? 'Selected' : 'Click to select' }}</div>
                                                        </div>
                                                        <div class="custom-control custom-radio">
                                                            <input type="radio"
                                                                class="custom-control-input tc-radio-input"
                                                                name="tpl_{{ $col }}"
                                                                id="tpl_{{ $col }}_{{ $ver }}"
                                                                value="{{ $ver }}"
                                                                data-column="{{ $col }}"
                                                                {{ $isSelected ? 'checked' : '' }}>
                                                            <label class="custom-control-label"
                                                                for="tpl_{{ $col }}_{{ $ver }}">Select</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection


@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (window.axios && csrf) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
            }

            const saveUrl = "{{ route('SaveTemplateChoice') }}";

            function setSelectedCard(column, version) {
                document.querySelectorAll(`.tc-option[data-column="${column}"]`).forEach(el => {
                    el.classList.toggle('selected', el.getAttribute('data-version') === version);
                });
                const badge = document.querySelector(`.tc-current[data-column="${column}"]`);
                if (badge) badge.textContent = version;
            }

            document.querySelectorAll('.tc-radio-input').forEach((input) => {
                input.addEventListener('change', async function(e) {
                    const column = this.getAttribute('data-column');
                    const version = this.value;

                    const previous = document.querySelector(`.tc-current[data-column="${column}"]`)
                        ?.textContent?.trim() || 'v1';

                    // Prevent no-op
                    if (previous === version) {
                        setSelectedCard(column, version);
                        return;
                    }

                    const result = await Swal.fire({
                        title: 'Confirm change?',
                        text: `Set ${column.replaceAll('_', ' ')} to ${version}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, update',
                        cancelButtonText: 'Cancel'
                    });

                    if (!result.isConfirmed) {
                        // revert UI
                        const prevRadio = document.querySelector(
                            `input.tc-radio-input[name="tpl_${column}"][value="${previous}"]`);
                        if (prevRadio) prevRadio.checked = true;
                        setSelectedCard(column, previous);
                        return;
                    }

                    try {
                        const res = await axios.post(saveUrl, {
                            column,
                            version
                        });
                        setSelectedCard(column, version);
                        if (window.toastr) toastr.success(res?.data?.message || 'Saved');
                    } catch (err) {
                        const msg = err?.response?.data?.message ||
                            'Failed to save template choice.';
                        if (window.toastr) toastr.error(msg);

                        // revert UI on failure
                        const prevRadio = document.querySelector(
                            `input.tc-radio-input[name="tpl_${column}"][value="${previous}"]`);
                        if (prevRadio) prevRadio.checked = true;
                        setSelectedCard(column, previous);
                    }
                });
            });

            // Ensure initial card state matches checked radios
            document.querySelectorAll('.tc-radio-input:checked').forEach((input) => {
                const column = input.getAttribute('data-column');
                setSelectedCard(column, input.value);
            });
        })();
    </script>
@endsection
