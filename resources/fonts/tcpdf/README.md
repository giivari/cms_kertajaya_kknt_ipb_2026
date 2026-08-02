# TCPDF core font descriptors

The JSON files in `core/` are generated font metrics for the standard PDF
Helvetica family. They were generated with the installed
`tecnickcom/tc-lib-pdf-font` importer from Adobe Core 14 AFM sources provided
by the official `tecnickcom/tc-font-mirror` 2.2.0 release.

Only font descriptors are stored here. No font binary is bundled or fetched at
runtime. PDF export resolves Composer-provided descriptors first, then this
portable project-owned fallback.
