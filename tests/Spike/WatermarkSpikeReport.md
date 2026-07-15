# Watermark Technical Spike Report

**Date:** 2026-07-15
**Goal:** Evaluate invisible watermark survivability for images and PDFs.

## 1. Methodology

The spike tested two image approaches and one PDF approach:
1. **LSB Steganography (Image):** Modifying the least significant bit of the red channel to embed a binary payload.
2. **EOF Metadata (Image):** Appending an HTML-style comment `<!-- WATERMARK: PAYLOAD -->` to the end of the file.
3. **EOF Metadata (PDF):** Appending a `%Watermark: PAYLOAD` comment to the end of a PDF structure.

Transformations tested on images:
- JPEG Compression (Quality 75)
- WebP Conversion
- Resize (50%)
- Crop (10%)

## 2. Results

### 2.1. Image: LSB Steganography
- **Baseline Extraction:** ✅ Success
- **JPEG Compression:** ❌ Failed (Lossy compression destroys LSB data)
- **WebP Conversion:** ❌ Failed (Lossy compression destroys LSB data)
- **Resize:** ❌ Failed (Interpolation alters pixel values)
- **Crop:** ❌ Failed (Payload offset shifts, destroying extraction unless a search-window or repeating payload is implemented)

### 2.2. Image: EOF Metadata
- **Baseline Extraction:** ✅ Success
- **JPEG Compression:** ❌ Failed (GD library discards non-image data upon resaving)
- **WebP Conversion:** ❌ Failed (GD discards EOF data)
- **Resize:** ❌ Failed
- **Crop:** ❌ Failed

### 2.3. PDF: EOF Metadata
- **Baseline Extraction:** ✅ Success
- *Note:* PDFs are generally immutable in a standard web delivery pipeline unless explicitly processed by a PDF engine. Appended EOF metadata survives standard downloading and sharing.

## 3. Conclusions and Recommendations

1. **Images:** Basic invisible watermarking (LSB and EOF/Structured Metadata) is highly fragile. Any standard image transformation (lossy compression, resizing) or third-party re-saving strips the watermark/identifier.
2. **PDFs:** EOF metadata injection is resilient for standard download/view scenarios. However, if a third-party processes the PDF using a tool that rebuilds the PDF structure (flattening, re-saving without extraneous EOF data), the identifier is stripped.

### Accepted MVP Wording
All system-generated public derivatives must receive and pass verification of an invisible identifier after all transformations are complete. Third-party reprocessing may remove the identifier and this limitation must be documented.
