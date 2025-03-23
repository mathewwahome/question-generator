
#!/usr/bin/env python3
import argparse
import json
import sys
from pdf_processor import PDFProcessor
from question_generator import QuestionGenerator

def main():
    parser = argparse.ArgumentParser(description='Generate questions from text or PDF')
    
    input_group = parser.add_mutually_exclusive_group(required=True)
    input_group.add_argument('--pdf', help='Path to PDF file')
    input_group.add_argument('--text', help='Path to text file or text content')
    
    parser.add_argument('--num', type=int, default=5, help='Number of questions to generate')
    args = parser.parse_args()
    
    # Initialize components
    pdf_processor = PDFProcessor()
    question_generator = QuestionGenerator()
    
    try:
        if args.pdf:
            # Extract text from PDF
            pdf_text = pdf_processor.extract_text_from_pdf(args.pdf)
            questions = question_generator.generate_from_text(pdf_text, args.num)
        elif args.text:
            # Check if args.text is a file or direct text
            try:
                with open(args.text, 'r', encoding='utf-8') as file:
                    text = file.read()
            except (IOError, FileNotFoundError):
                # If not a file, assume it's direct text
                text = args.text
                
            questions = question_generator.generate_from_text(text, args.num)
        
        # Output results as JSON
        result = {
            'success': True,
            'questions': questions
        }
        print(json.dumps(result))
        
    except Exception as e:
        error_result = {
            'success': False,
            'error': str(e)
        }
        print(json.dumps(error_result))
        sys.exit(1)

if __name__ == "__main__":
    main()