import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

# Brand Colors
COLOR_DARK_GREEN = RGBColor(7, 36, 18)     # #072412
COLOR_BRAND_GREEN = RGBColor(29, 106, 52)  # #1D6A34
COLOR_ACCENT_MINT = RGBColor(55, 206, 151) # #37CE97
COLOR_DARK_SLATE = RGBColor(30, 41, 59)    # #1E293B
COLOR_MUTED_SLATE = RGBColor(100, 116, 139)# #64748B
COLOR_WHITE = RGBColor(255, 255, 255)

HEX_DARK_GREEN = "072412"
HEX_BRAND_GREEN = "1D6A34"
HEX_ACCENT_MINT = "37CE97"
HEX_LIGHT_BG = "F8FAFC"
HEX_BORDER = "CBD5E1"
HEX_WARN_BG = "FEF9C3"
HEX_WARN_BORDER = "F59E0B"
HEX_INFO_BG = "EFF6FF"
HEX_INFO_BORDER = "3B82F6"

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    """Set inner cell padding in twips (1 pt = 20 twips)"""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)

def set_cell_background(cell, hex_color):
    """Set background color of a table cell"""
    shading_xml = f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>'
    cell._tc.get_or_add_tcPr().append(parse_xml(shading_xml))

def set_cell_border(cell, **kwargs):
    """
    Set cell borders: top, bottom, left, right.
    kwargs format: top={"sz": 4, "val": "single", "color": "E2E8F0"}
    """
    tcPr = cell._tc.get_or_add_tcPr()
    tcBorders = OxmlElement('w:tcBorders')
    for edge in ('top', 'left', 'bottom', 'right', 'insideH', 'insideV'):
        edge_data = kwargs.get(edge)
        if edge_data:
            tag = 'w:{}'.format(edge)
            element = OxmlElement(tag)
            element.set(qn('w:val'), edge_data.get('val', 'single'))
            element.set(qn('w:sz'), str(edge_data.get('sz', 4)))
            element.set(qn('w:space'), '0')
            element.set(qn('w:color'), edge_data.get('color', 'E2E8F0'))
            tcBorders.append(element)
    tcPr.append(tcBorders)

def add_header_footer(doc, title_text):
    """Add professional running header and footer with page numbering"""
    for section in doc.sections:
        section.top_margin = Inches(0.8)
        section.bottom_margin = Inches(0.8)
        section.left_margin = Inches(0.8)
        section.right_margin = Inches(0.8)

        # Header
        header = section.header
        hp = header.paragraphs[0]
        hp.text = f"TalentHub Consultant  |  {title_text}"
        hp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        hp.runs[0].font.name = "Segoe UI"
        hp.runs[0].font.size = Pt(8.5)
        hp.runs[0].font.color.rgb = COLOR_MUTED_SLATE

        # Footer
        footer = section.footer
        fp = footer.paragraphs[0]
        fp.text = "CONFIDENTIAL & PROPRIETARY  —  TalentHub Recruitment Operations Center  |  "
        fp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        fp.runs[0].font.name = "Segoe UI"
        fp.runs[0].font.size = Pt(8.5)
        fp.runs[0].font.color.rgb = COLOR_MUTED_SLATE

def add_cover_page(doc, doc_title, doc_subtitle, doc_ref, doc_type="TECHNICAL OPERATING MANUAL"):
    """Create a stunning corporate cover page"""
    # Top banner paragraph
    p_top = doc.add_paragraph()
    p_top.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    r_badge = p_top.add_run(f"TALENTHUB ENTERPRISE DOCUMENTATION  •  {doc_ref}\n")
    r_badge.font.name = "Segoe UI"
    r_badge.font.size = Pt(9)
    r_badge.font.bold = True
    r_badge.font.color.rgb = COLOR_BRAND_GREEN

    # Logo if available
    logo_path = os.path.join(os.path.dirname(__file__), "..", "screenshots", "talenthub_logo.png")
    if os.path.exists(logo_path):
        p_logo = doc.add_paragraph()
        p_logo.alignment = WD_ALIGN_PARAGRAPH.LEFT
        doc.add_picture(logo_path, width=Inches(2.8))

    doc.add_paragraph() # Spacing

    # Category Pill
    p_cat = doc.add_paragraph()
    r_cat = p_cat.add_run(f"  {doc_type.upper()}  ")
    r_cat.font.name = "Segoe UI"
    r_cat.font.size = Pt(9.5)
    r_cat.font.bold = True
    r_cat.font.color.rgb = COLOR_WHITE
    # Highlight pill via XML background
    r_cat_elem = r_cat._r.get_or_add_rPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{HEX_BRAND_GREEN}"/>')
    r_cat_elem.append(shd)

    # Document Title
    p_title = doc.add_paragraph()
    p_title.paragraph_format.space_before = Pt(12)
    p_title.paragraph_format.space_after = Pt(6)
    r_title = p_title.add_run(doc_title)
    r_title.font.name = "Segoe UI"
    r_title.font.size = Pt(26)
    r_title.font.bold = True
    r_title.font.color.rgb = COLOR_DARK_GREEN

    # Subtitle
    p_sub = doc.add_paragraph()
    p_sub.paragraph_format.space_after = Pt(28)
    r_sub = p_sub.add_run(doc_subtitle)
    r_sub.font.name = "Segoe UI"
    r_sub.font.size = Pt(12.5)
    r_sub.font.color.rgb = COLOR_MUTED_SLATE

    # Metadata Table
    table = doc.add_table(rows=6, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    meta_rows = [
        ("Platform Target:", "TalentHub Recruitment Consultancy Platform"),
        ("Live Production URL:", "https://talenthubconsultant.com/"),
        ("Core Technology:", "PHP 8.2 / 8.3  •  CodeIgniter 4.5.x  •  MySQL 8.0+  •  Tailwind CSS  •  PWA"),
        ("Version & Status:", "Version 2.4.0 (Production Verified Baseline)"),
        ("Classification:", "Internal Operations, Staff Administration & Technical Reference"),
        ("Publication Date:", "November 2026")
    ]

    for idx, (label, val) in enumerate(meta_rows):
        row = table.rows[idx]
        c0, c1 = row.cells[0], row.cells[1]
        c0.width = Inches(2.2)
        c1.width = Inches(4.5)

        set_cell_margins(c0, 80, 80, 100, 100)
        set_cell_margins(c1, 80, 80, 100, 100)
        set_cell_background(c0, "F1F5F9")
        set_cell_background(c1, "FFFFFF")

        p0 = c0.paragraphs[0]
        p0.text = label
        p0.runs[0].font.name = "Segoe UI"
        p0.runs[0].font.size = Pt(9.5)
        p0.runs[0].font.bold = True
        p0.runs[0].font.color.rgb = COLOR_DARK_SLATE

        p1 = c1.paragraphs[0]
        p1.text = val
        p1.runs[0].font.name = "Segoe UI"
        p1.runs[0].font.size = Pt(9.5)
        p1.runs[0].font.color.rgb = COLOR_DARK_SLATE

        b_style = {"val": "single", "sz": 4, "color": "E2E8F0"}
        set_cell_border(c0, top=b_style, bottom=b_style, left=b_style, right=b_style)
        set_cell_border(c1, top=b_style, bottom=b_style, left=b_style, right=b_style)

    doc.add_page_break()

def add_heading_1(doc, text):
    """Add professional Heading 1 with accent bar"""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(20)
    p.paragraph_format.space_after = Pt(8)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Segoe UI"
    r.font.size = Pt(17)
    r.font.bold = True
    r.font.color.rgb = COLOR_DARK_GREEN
    return p

def add_heading_2(doc, text):
    """Add professional Heading 2"""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Segoe UI"
    r.font.size = Pt(13)
    r.font.bold = True
    r.font.color.rgb = COLOR_BRAND_GREEN
    return p

def add_heading_3(doc, text):
    """Add Heading 3"""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(10)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = "Segoe UI"
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = COLOR_DARK_SLATE
    return p

def add_paragraph(doc, text, bold_prefix=None, italic=False):
    """Add formatted body paragraph"""
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix + " ")
        r_pre.font.name = "Segoe UI"
        r_pre.font.size = Pt(10)
        r_pre.font.bold = True
        r_pre.font.color.rgb = COLOR_DARK_SLATE
    r = p.add_run(text)
    r.font.name = "Segoe UI"
    r.font.size = Pt(10)
    r.font.italic = italic
    r.font.color.rgb = COLOR_DARK_SLATE
    return p

def add_bullet(doc, text, bold_prefix=None):
    """Add styled bullet item"""
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix + ": ")
        r_pre.font.name = "Segoe UI"
        r_pre.font.size = Pt(10)
        r_pre.font.bold = True
        r_pre.font.color.rgb = COLOR_DARK_SLATE
    r = p.add_run(text)
    r.font.name = "Segoe UI"
    r.font.size = Pt(10)
    r.font.color.rgb = COLOR_DARK_SLATE
    return p

def add_callout(doc, text, title="IMPORTANT NOTICE", callout_type="info"):
    """Create a high-contrast styled callout box"""
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    cell = table.cell(0, 0)
    cell.width = Inches(6.8)

    set_cell_margins(cell, top=100, bottom=100, left=150, right=150)

    hex_bg = HEX_INFO_BG if callout_type == "info" else (HEX_WARN_BG if callout_type == "warn" else "DEF7EC")
    hex_border = HEX_INFO_BORDER if callout_type == "info" else (HEX_WARN_BORDER if callout_type == "warn" else "1D6A34")
    border_color = RGBColor(59, 130, 246) if callout_type == "info" else (RGBColor(245, 158, 11) if callout_type == "warn" else COLOR_BRAND_GREEN)

    set_cell_background(cell, hex_bg)
    left_b = {"val": "single", "sz": 24, "color": hex_border}
    thin_b = {"val": "single", "sz": 4, "color": "E2E8F0"}
    set_cell_border(cell, left=left_b, top=thin_b, bottom=thin_b, right=thin_b)

    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(2)
    r_t = p.add_run(f"[{title}]  ")
    r_t.font.name = "Segoe UI"
    r_t.font.size = Pt(9.5)
    r_t.font.bold = True
    r_t.font.color.rgb = border_color

    r_c = p.add_run(text)
    r_c.font.name = "Segoe UI"
    r_c.font.size = Pt(9.5)
    r_c.font.color.rgb = COLOR_DARK_SLATE

    doc.add_paragraph().paragraph_format.space_after = Pt(4)

def add_styled_table(doc, headers, data, col_widths=None):
    """Add a beautifully styled corporate data table"""
    table = doc.add_table(rows=len(data) + 1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    # Header Row
    hdr_row = table.rows[0]
    for idx, h_text in enumerate(headers):
        cell = hdr_row.cells[idx]
        if col_widths and idx < len(col_widths):
            cell.width = Inches(col_widths[idx])
        set_cell_margins(cell, top=100, bottom=100, left=120, right=120)
        set_cell_background(cell, HEX_DARK_GREEN)
        
        b_hdr = {"val": "single", "sz": 6, "color": HEX_DARK_GREEN}
        set_cell_border(cell, top=b_hdr, bottom=b_hdr, left=b_hdr, right=b_hdr)

        p = cell.paragraphs[0]
        p.text = h_text
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.runs[0].font.name = "Segoe UI"
        p.runs[0].font.size = Pt(9.5)
        p.runs[0].font.bold = True
        p.runs[0].font.color.rgb = COLOR_WHITE

    # Data Rows
    for r_idx, row_data in enumerate(data, start=1):
        row = table.rows[r_idx]
        bg_color = "FFFFFF" if r_idx % 2 != 0 else "F8FAFC"
        for c_idx, val in enumerate(row_data):
            cell = row.cells[c_idx]
            if col_widths and c_idx < len(col_widths):
                cell.width = Inches(col_widths[c_idx])
            set_cell_margins(cell, top=80, bottom=80, left=120, right=120)
            set_cell_background(cell, bg_color)
            
            b_cell = {"val": "single", "sz": 4, "color": "E2E8F0"}
            set_cell_border(cell, top=b_cell, bottom=b_cell, left=b_cell, right=b_cell)

            p = cell.paragraphs[0]
            p.text = str(val)
            p.runs[0].font.name = "Segoe UI"
            p.runs[0].font.size = Pt(9)
            p.runs[0].font.color.rgb = COLOR_DARK_SLATE
            
            # Align center for short IDs, statuses, or codes
            if c_idx == 0 or len(str(val)) <= 8 or str(val).isupper():
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT

    doc.add_paragraph().paragraph_format.space_after = Pt(6)
    return table

