import PyPDF2
import nltk
from nltk.tokenize import sent_tokenize
import os

# Download necessary NLTK data
nltk.download('punkt')

class PDFProcessor:
    def __init__(self):
        self.text_cache = {}
    
    def extract_text_from_pdf(self, pdf_path):
        """Extract full text from a PDF file"""
        if pdf_path in self.text_cache:
            return self.text_cache[pdf_path]
            
        if not os.path.exists(pdf_path):
            raise FileNotFoundError(f"PDF file not found: {pdf_path}")
            
        try:
            text = ""
            with open(pdf_path, 'rb') as file:
                reader = PyPDF2.PdfReader(file)
                for page_num in range(len(reader.pages)):
                    page = reader.pages[page_num]
                    text += page.extract_text() + "\n"
                    
            # Cache the extracted text
            self.text_cache[pdf_path] = text
            return text
        except Exception as e:
            raise Exception(f"Error extracting text from PDF: {str(e)}")
    
    def get_sentences(self, pdf_path):
        """Extract sentences from PDF text"""
        text = self.extract_text_from_pdf(pdf_path)
        sentences = sent_tokenize(text)
        return sentences