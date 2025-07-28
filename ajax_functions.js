// AJAX Functions for Student Management System

// Live search functionality
function liveSearch(searchTerm, searchType) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_search.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const results = JSON.parse(xhr.responseText);
            displaySearchResults(results, searchType);
        }
    };
    
    xhr.send('search=' + encodeURIComponent(searchTerm) + '&type=' + searchType);
}

// Display search results
function displaySearchResults(results, searchType) {
    const container = document.getElementById(searchType + '-results');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<p class="text-muted">No results found</p>';
        return;
    }
    
    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'search-result-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                <div>
                    <strong>${item.name}</strong>
                    <br><small class="text-muted">${item.details}</small>
                </div>
                <button class="btn btn-sm btn-primary" onclick="selectItem('${item.id}', '${item.name}', '${searchType}')">
                    Select
                </button>
            </div>
        `;
        container.appendChild(div);
    });
}

// Select item from search results
function selectItem(id, name, type) {
    const input = document.getElementById(type + '_id');
    const display = document.getElementById(type + '_display');
    
    if (input) input.value = id;
    if (display) display.textContent = name;
    
    // Hide search results
    const results = document.getElementById(type + '-results');
    if (results) results.innerHTML = '';
}

// Load attendance data dynamically
function loadAttendanceData(classId, subjectId, date) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_attendance.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            displayAttendanceForm(data);
        }
    };
    
    xhr.send('class_id=' + classId + '&subject_id=' + subjectId + '&date=' + date);
}

// Display attendance form with loaded data
function displayAttendanceForm(data) {
    const container = document.getElementById('attendance-form');
    if (!container) return;
    
    let html = '<form method="POST" action="attendance.php">';
    html += '<input type="hidden" name="class_id" value="' + data.class_id + '">';
    html += '<input type="hidden" name="subject_id" value="' + data.subject_id + '">';
    html += '<input type="hidden" name="date" value="' + data.date + '">';
    
    html += '<div class="table-responsive">';
    html += '<table class="table table-striped">';
    html += '<thead><tr><th>Student</th><th>Roll Number</th><th>Status</th></tr></thead>';
    html += '<tbody>';
    
    data.students.forEach(student => {
        html += '<tr>';
        html += '<td>' + student.full_name + '</td>';
        html += '<td>' + (student.roll_number || 'N/A') + '</td>';
        html += '<td>';
        html += '<select name="attendance[' + student.id + ']" class="form-select" required>';
        html += '<option value="present" ' + (student.status === 'present' ? 'selected' : '') + '>Present</option>';
        html += '<option value="absent" ' + (student.status === 'absent' ? 'selected' : '') + '>Absent</option>';
        html += '<option value="late" ' + (student.status === 'late' ? 'selected' : '') + '>Late</option>';
        html += '<option value="excused" ' + (student.status === 'excused' ? 'selected' : '') + '>Excused</option>';
        html += '</select>';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += '<button type="submit" class="btn btn-primary">Save Attendance</button>';
    html += '</form>';
    
    container.innerHTML = html;
}

// Load marks data dynamically
function loadMarksData(studentId, subjectId, classId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_marks.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            displayMarksForm(data);
        }
    };
    
    xhr.send('student_id=' + studentId + '&subject_id=' + subjectId + '&class_id=' + classId);
}

// Display marks form with loaded data
function displayMarksForm(data) {
    const container = document.getElementById('marks-form');
    if (!container) return;
    
    let html = '<form method="POST" action="marks.php">';
    html += '<input type="hidden" name="student_id" value="' + data.student_id + '">';
    html += '<input type="hidden" name="subject_id" value="' + data.subject_id + '">';
    html += '<input type="hidden" name="class_id" value="' + data.class_id + '">';
    
    html += '<div class="row">';
    html += '<div class="col-md-6">';
    html += '<div class="mb-3">';
    html += '<label class="form-label">Student</label>';
    html += '<input type="text" class="form-control" value="' + data.student_name + '" readonly>';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-6">';
    html += '<div class="mb-3">';
    html += '<label class="form-label">Subject</label>';
    html += '<input type="text" class="form-control" value="' + data.subject_name + '" readonly>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    html += '<div class="row">';
    html += '<div class="col-md-4">';
    html += '<div class="mb-3">';
    html += '<label class="form-label">Exam Type</label>';
    html += '<select name="exam_type" class="form-control" required>';
    html += '<option value="quiz">Quiz</option>';
    html += '<option value="midterm">Midterm</option>';
    html += '<option value="final">Final</option>';
    html += '<option value="assignment">Assignment</option>';
    html += '<option value="project">Project</option>';
    html += '</select>';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-4">';
    html += '<div class="mb-3">';
    html += '<label class="form-label">Marks Obtained</label>';
    html += '<input type="number" name="marks_obtained" class="form-control" step="0.01" min="0" required>';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-4">';
    html += '<div class="mb-3">';
    html += '<label class="form-label">Total Marks</label>';
    html += '<input type="number" name="total_marks" class="form-control" step="0.01" min="0" required>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    html += '<div class="mb-3">';
    html += '<label class="form-label">Exam Date</label>';
    html += '<input type="date" name="exam_date" class="form-control" required>';
    html += '</div>';
    
    html += '<div class="mb-3">';
    html += '<label class="form-label">Remarks</label>';
    html += '<textarea name="remarks" class="form-control" rows="3"></textarea>';
    html += '</div>';
    
    html += '<button type="submit" class="btn btn-primary">Save Marks</button>';
    html += '</form>';
    
    container.innerHTML = html;
}

// Real-time notifications
function checkNotifications() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_notifications.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            updateNotificationBadge(data.count);
        }
    };
    
    xhr.send();
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Auto-refresh notifications every 30 seconds
setInterval(checkNotifications, 30000);

// Initialize AJAX functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add search functionality to search inputs
    const searchInputs = document.querySelectorAll('.live-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value;
            const searchType = this.dataset.searchType;
            if (searchTerm.length >= 2) {
                liveSearch(searchTerm, searchType);
            }
        });
    });
    
    // Add dynamic loading for attendance
    const classSelect = document.getElementById('class_id');
    const subjectSelect = document.getElementById('subject_id');
    const dateInput = document.getElementById('date');
    
    if (classSelect && subjectSelect && dateInput) {
        function loadAttendance() {
            if (classSelect.value && subjectSelect.value && dateInput.value) {
                loadAttendanceData(classSelect.value, subjectSelect.value, dateInput.value);
            }
        }
        
        classSelect.addEventListener('change', loadAttendance);
        subjectSelect.addEventListener('change', loadAttendance);
        dateInput.addEventListener('change', loadAttendance);
    }
    
    // Add dynamic loading for marks
    const studentSelect = document.getElementById('student_id');
    const marksSubjectSelect = document.getElementById('subject_id');
    const marksClassSelect = document.getElementById('class_id');
    
    if (studentSelect && marksSubjectSelect && marksClassSelect) {
        function loadMarks() {
            if (studentSelect.value && marksSubjectSelect.value && marksClassSelect.value) {
                loadMarksData(studentSelect.value, marksSubjectSelect.value, marksClassSelect.value);
            }
        }
        
        studentSelect.addEventListener('change', loadMarks);
        marksSubjectSelect.addEventListener('change', loadMarks);
        marksClassSelect.addEventListener('change', loadMarks);
    }
}); 