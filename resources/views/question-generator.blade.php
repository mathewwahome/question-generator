<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .tab-content { padding: 20px; border: 1px solid #dee2e6; border-top: 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Question Generator</h1>
        
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pdf-tab" data-bs-toggle="tab" data-bs-target="#pdf" type="button" role="tab">PDF Upload</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">Database Notes</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- PDF Upload Tab -->
            <div class="tab-pane fade show active" id="pdf" role="tabpanel">
                <form id="pdf-form">
                    <div class="mb-3">
                        <label for="pdf_file" class="form-label">Select PDF File</label>
                        <input class="form-control" type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                    </div>
                    <div class="mb-3">
                        <label for="pdf_num_questions" class="form-label">Number of Questions</label>
                        <input type="number" class="form-control" id="pdf_num_questions" name="num_questions" min="1" max="20" value="5">
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Questions</button>
                </form>
            </div>
            
            <!-- Notes Tab -->
            <div class="tab-pane fade" id="notes" role="tabpanel">
                <form id="notes-form">
                    <div class="mb-3">
                        <label class="form-label">Select Notes</label>
                        <div id="notes-list" class="form-control" style="height: 200px; overflow-y: scroll;">
                            <!-- Notes will be loaded here -->
                            <div class="d-flex justify-content-center align-items-center h-100">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes_num_questions" class="form-label">Number of Questions</label>
                        <input type="number" class="form-control" id="notes_num_questions" name="num_questions" min="1" max="20" value="5">
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Questions</button>
                </form>
            </div>
        </div>
        
        <!-- Results Section -->
        <div id="results" class="mt-4" style="display: none;">
            <div class="card">
                <div class="card-header">
                    Generated Questions
                </div>
                <div class="card-body">
                    <ul id="questions-list" class="list-group list-group-flush">
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load notes from database
            fetch('/api/notes')
                .then(response => response.json())
                .then(data => {
                    const notesList = document.getElementById('notes-list');
                    notesList.innerHTML = '';
                    
                    if (data.notes.length === 0) {
                        notesList.innerHTML = '<p class="text-center">No notes found</p>';
                        return;
                    }
                    
                    data.notes.forEach(note => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" value="${note.id}" id="note-${note.id}" name="note_ids[]">
                            <label class="form-check-label" for="note-${note.id}">
                                ${note.title || 'Untitled'} (${new Date(note.created_at).toLocaleDateString()})
                            </label>
                        `;
                        notesList.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading notes:', error);
                    document.getElementById('notes-list').innerHTML = '<p class="text-center text-danger">Error loading notes</p>';
                });
            
            // Handle PDF form submission
            document.getElementById('pdf-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('pdf_file', document.getElementById('pdf_file').files[0]);
                formData.append('num_questions', document.getElementById('pdf_num_questions').value);
                
                generateQuestions('/api/generate-questions/pdf', formData);
            });
            
            // Handle Notes form submission
            document.getElementById('notes-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const noteIds = Array.from(document.querySelectorAll('input[name="note_ids[]"]:checked')).map(cb => cb.value);
                
                if (noteIds.length === 0) {
                    alert('Please select at least one note.');
                    return;
                }
                
                const data = {
                    note_ids: noteIds,
                    num_questions: document.getElementById('notes_num_questions').value
                };
                
                generateQuestions('/api/generate-questions/notes', data, true);
            });
            
            // Function to generate questions
            function generateQuestions(url, data, isJson = false) {
                // Show loading
                document.getElementById('questions-list').innerHTML = `
                    <div class="d-flex justify-content-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                document.getElementById('results').style.display = 'block';
                
                const fetchOptions = {
                    method: 'POST',
                    headers: {}
                };
                
                if (isJson) {
                    fetchOptions.headers['Content-Type'] = 'application/json';
                    fetchOptions.body = JSON.stringify(data);
                } else {
                    fetchOptions.body = data;
                }
                
                fetch(url, fetchOptions)
                    .then(response => response.json())
                    .then(data => {
                        const questionsList = document.getElementById('questions-list');
                        questionsList.innerHTML = '';
                        
                        if (data.success && data.questions.length > 0) {
                            data.questions.forEach((question, index) => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item';
                                li.innerHTML = `<strong>${index + 1}.</strong> ${question}`;
                                questionsList.appendChild(li);
                            });
                        } else {
                            questionsList.innerHTML = `<p class="text-center text-danger">${data.message || 'Failed to generate questions'}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error generating questions:', error);
                        document.getElementById('questions-list').innerHTML = '<p class="text-center text-danger">Error generating questions</p>';
                    });
            }
        });
    </script>
</body>
</html>