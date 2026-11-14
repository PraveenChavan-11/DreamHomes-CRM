with open('public/assets/css/custom.css', 'r', encoding='utf-8') as f:
    content = f.read()

target = '::selection {'
css_to_add = """
/* Core Brand Utilities for Guaranteed High-Contrast Rendering */
.bg-brand-primary, [class*="bg-brand-primary"] {
  background-color: #1D6A34 !important;
  color: #FFFFFF !important;
}
.bg-brand-primary:hover, .hover\\:bg-brand-hover:hover, [class*="hover:bg-brand-hover"]:hover {
  background-color: #155227 !important;
  color: #FFFFFF !important;
}
.bg-brand-dark, [class*="bg-brand-dark"] {
  background-color: #072412 !important;
  color: #FFFFFF !important;
}
.bg-brand-light, [class*="bg-brand-light"] {
  background-color: #EAF5EF !important;
  color: #1D6A34 !important;
}
.text-brand-primary, [class*="text-brand-primary"] {
  color: #1D6A34 !important;
}
.border-brand-primary, [class*="border-brand-primary"] {
  border-color: #1D6A34 !important;
}
.text-brand-accent {
  color: #37CE97 !important;
}
.bg-brand-accent {
  background-color: #37CE97 !important;
  color: #072412 !important;
}

"""

if target in content and '.bg-brand-primary' not in content:
    content = content.replace(target, css_to_add + target)
    with open('public/assets/css/custom.css', 'w', encoding='utf-8') as f:
        f.write(content)
    print('SUCCESS: Brand utility classes added to custom.css')
else:
    print('Target not found or already present')

