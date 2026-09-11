<style>
    [v-cloak] { display: none !important; }

    .package-tabs .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 0.9rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.25s ease;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: .05em;
    }
    .package-tabs .nav-link i { font-size: 16px; }
    .package-tabs .nav-link.active {
        background-color: #186dde;
        color: #fff;
        box-shadow: 0 0.25rem 0.75rem rgba(24, 109, 222, 0.35);
    }

    .tab-card {
        border-radius: 0.75rem;
        box-shadow: 0 0.75rem 1.5rem rgba(31,45,61,.08);
        border: none;
    }

    .hero-image-uploader,
    .meta-image-uploader {
        min-height: 240px;
        background-color: #f8f9fb;
        cursor: pointer;
    }
    .hero-image-uploader.has-image,
    .meta-image-uploader.has-image {
        padding: 0;
        background-color: transparent;
    }

    .gallery-slot {
        min-height: 120px;
        cursor: pointer;
        background-color: #f8f9fb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all .2s ease;
    }
    .gallery-slot:hover,
    .hero-image-uploader:hover,
    .meta-image-uploader:hover {
        border-color: #186dde;
        background-color: rgba(24,109,222,.05);
    }

    .catalog-results {
        max-height: 460px;
        overflow-y: auto;
    }

    .selected-items table tbody tr td {
        vertical-align: middle;
    }

    .highlight-list .input-group-text {
        background-color: rgba(40, 199, 111, 0.12);
        border: none;
        color: #28c76f;
    }
    .highlight-list .form-control {
        border-left: none;
    }
    .highlight-list .form-control:focus {
        box-shadow: none;
    }

    .tab-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,.05);
    }
    .tab-card .card-body {
        padding: 1.5rem;
    }
</style>