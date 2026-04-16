from zipfile import ZipFile
from pathlib import Path
from lxml import etree

def extract_tokens(doc_path: Path):
    with ZipFile(doc_path) as z:
        xml = z.read('word/document.xml')
    root = etree.fromstring(xml)
    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
    runs = root.xpath('.//w:r[w:rPr/w:color[@w:val="FF0000" or @w:val="EE0000"]]', namespaces=ns)
    result = []
    buffer = []
    for run in runs:
        texts = run.xpath('.//w:t/text()', namespaces=ns)
        if not texts:
            continue
        text = ''.join(texts).replace('\xa0', ' ')
        text = text.strip()
        if not text:
            continue
        buffer.append(text)
        if ')' in text:
            result.append(' '.join(buffer))
            buffer = []
    if buffer:
        result.append(' '.join(buffer))
    return result

if __name__ == '__main__':
    doc = Path(__file__).resolve().parent.parent / 'template' / 'berita_acara.docx'
    for token in extract_tokens(doc):
        print(token)
