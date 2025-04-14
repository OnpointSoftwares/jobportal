document.addEventListener('DOMContentLoaded', function() {
    // Load featured jobs
    loadFeaturedJobs();

    // Handle search form submission
    const searchForm = document.querySelector('.search-box form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const keyword = this.keyword.value;
            const location = this.location.value;
            searchJobs(keyword, location);
        });
    }
});

async function loadFeaturedJobs() {
    try {
        const response = await fetch('api/featured-jobs.php');
        const jobs = await response.json();
        displayJobs(jobs);
    } catch (error) {
        console.error('Error loading featured jobs:', error);
    }
}

function displayJobs(jobs) {
    const jobsContainer = document.getElementById('featuredJobs');
    if (!jobsContainer) return;

    jobsContainer.innerHTML = jobs.map(job => `
        <div class="job-card">
            <div class="job-card-header">
                <img src="${job.company_logo}" alt="${job.company_name}" class="company-logo">
                <div class="job-card-title">
                    <h3>${job.title}</h3>
                    <p class="company-name">${job.company_name}</p>
                </div>
            </div>
            <div class="job-card-details">
                <span><i class="fas fa-map-marker-alt"></i> ${job.location}</span>
                <span><i class="fas fa-dollar-sign"></i> ${job.salary}</span>
                <span><i class="fas fa-clock"></i> ${job.type}</span>
            </div>
            <p class="job-description">${job.description.substring(0, 150)}...</p>
            <div class="job-card-footer">
                <button onclick="applyForJob(${job.id})" class="apply-btn">Apply Now</button>
                <button onclick="saveJob(${job.id})" class="save-btn">
                    <i class="far fa-bookmark"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function searchJobs(keyword, location) {
    try {
        const response = await fetch(`api/search-jobs.php?keyword=${encodeURIComponent(keyword)}&location=${encodeURIComponent(location)}`);
        const jobs = await response.json();
        displayJobs(jobs);
    } catch (error) {
        console.error('Error searching jobs:', error);
    }
}

function applyForJob(jobId) {
    // Check if user is logged in
    const isLoggedIn = checkUserLogin();
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }

    // Handle job application
    fetch('api/apply-job.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Application submitted successfully!');
        } else {
            alert(data.message || 'Error submitting application');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting application');
    });
}

function saveJob(jobId) {
    // Check if user is logged in
    const isLoggedIn = checkUserLogin();
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }

    // Handle saving job
    fetch('api/save-job.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Job saved successfully!');
        } else {
            alert(data.message || 'Error saving job');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving job');
    });
}

function checkUserLogin() {
    // This should check for user session/token
    // For now, we'll just return false to redirect to login
    return false;
}
