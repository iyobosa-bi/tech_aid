// Scroll cues for the shared ticket table (resources/views/tickets/partials/table.blade.php):
// a shadow beside the pinned ID column once scrolled sideways, and a fade on the right edge
// while more columns are hidden. Alpine starts one per table — including tables that live
// search swaps in later — and tears its listeners down when the table is removed.
function ticketTableScroll() {
    return {
        scrolled: false,
        moreRight: false,

        init() {
            this.$nextTick(() => this.sync());
            document.fonts?.ready.then(() => this.sync()); // Poppins loading late changes column widths
        },

        sync() {
            const { scrollLeft, clientWidth, scrollWidth } = this.$refs.scroller;
            this.scrolled = scrollLeft > 0;
            this.moreRight = scrollLeft + clientWidth < scrollWidth - 1;
        },
    };
}
