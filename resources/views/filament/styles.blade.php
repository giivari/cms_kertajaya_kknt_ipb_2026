<style>
    /* 1. Nuke ALL Whitespace at the Top */
    .fi-main, .fi-page, .fi-header, .fi-main-ctn {
        padding-top: 0 !important;
        margin-top: 0 !important;
        gap: 0.25rem !important;
    }
    
    .fi-breadcrumbs {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    .fi-header-heading {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    /* 2. Responsive Sidebar (Full Height Distribution) */
    .fi-sidebar-nav {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 1vh 1rem !important; /* Adapt to viewport height */
        gap: 0 !important;
    }
    
    .fi-sidebar-nav-groups {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-evenly !important;
        flex-grow: 1 !important;
        gap: 0 !important;
    }
    
    .fi-sidebar-group {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }
    
    .fi-sidebar-group-items {
        gap: 0.25rem !important;
    }
    
    .fi-sidebar-item-btn {
        min-height: 2.2rem !important; /* Minimum touch target but compact */
        padding: 0.4rem 0.75rem !important;
    }
</style>
