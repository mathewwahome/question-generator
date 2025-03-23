import spacy
import random
from nltk.corpus import wordnet
import re

class QuestionGenerator:
    def __init__(self):
        # Load English language model
        self.nlp = spacy.load("en_core_web_sm")
        
        # Question templates
        self.templates = {
            "DEFINITION": [
                "What is the definition of {entity}?",
                "How would you define {entity}?",
                "What does the term {entity} mean in this context?"
            ],
            "PROCESS": [
                "What are the steps involved in {entity}?",
                "How does {entity} work?",
                "Can you explain the process of {entity}?"
            ],
            "COMPARISON": [
                "What is the difference between {entity} and {entity2}?",
                "How does {entity} compare to {entity2}?",
                "What are the similarities and differences between {entity} and {entity2}?"
            ],
            "APPLICATION": [
                "How is {entity} applied in practice?",
                "What are some applications of {entity}?",
                "How can {entity} be used in real-world scenarios?"
            ],
            "FACTUAL": [
                "What is {entity}?",
                "Who developed {entity}?",
                "When was {entity} first introduced?"
            ]
        }
    
    def _find_related_entity(self, entities, target_entity):
        """Find a different entity that might be related for comparison questions"""
        filtered = [e for e in entities if e.lower() != target_entity.lower()]
        return random.choice(filtered) if filtered else target_entity
    
    def generate_from_text(self, text, num_questions=5):
        """Generate questions from a given text"""
        doc = self.nlp(text)
        
        # Extract key entities (nouns, proper nouns)
        entities = []
        for ent in doc.ents:
            entities.append(ent.text)
        
        # Add important noun phrases
        for chunk in doc.noun_chunks:
            if len(chunk.text.split()) <= 3:  # Limit to short phrases
                entities.append(chunk.text)
        
        # Use key verbs for process questions
        processes = []
        for token in doc:
            if token.pos_ == "VERB" and token.is_alpha and len(token.text) > 3:
                processes.append(token.text)
        
        # Generate questions
        questions = []
        if entities:
            # Remove duplicates and limit entities
            unique_entities = list(set(entities))
            selected_entities = random.sample(unique_entities, min(len(unique_entities), num_questions*2))
            
            for entity in selected_entities:
                if len(questions) >= num_questions:
                    break
                    
                # Randomly select question type
                q_type = random.choice(list(self.templates.keys()))
                
                if q_type == "COMPARISON" and len(unique_entities) > 1:
                    entity2 = self._find_related_entity(unique_entities, entity)
                    template = random.choice(self.templates[q_type])
                    questions.append(template.format(entity=entity, entity2=entity2))
                else:
                    template = random.choice(self.templates[q_type])
                    questions.append(template.format(entity=entity))
        
        # If we still need more questions, try process-based questions
        if processes and len(questions) < num_questions:
            for process in processes:
                if len(questions) >= num_questions:
                    break
                template = random.choice(self.templates["PROCESS"])
                questions.append(template.format(entity=process))
        
        # Deduplicate and return
        return list(set(questions))[:num_questions]
    
    
    
    