    </div> <!-- End main-content -->

    <footer class="bg-dark text-light mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3 text-white fw-bold">About JobPortal</h5>
                    <p class="text-white-50">Your trusted platform for finding the perfect job match. Connect with top employers and discover exciting career opportunities.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3 text-white fw-bold">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none hover-white">About Us</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white-50 text-decoration-none hover-white">Contact</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-white-50 text-decoration-none hover-white">Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-white-50 text-decoration-none hover-white">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3 text-white fw-bold">For Job Seekers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="jobs.php" class="text-white-50 text-decoration-none hover-white">Browse Jobs</a></li>
                        <li class="mb-2"><a href="companies.php" class="text-white-50 text-decoration-none hover-white">Companies</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-white">Career Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-white">Resume Tips</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3 text-white fw-bold">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-white-50"><i class="fas fa-envelope me-2"></i> info@jobportal.com</li>
                        <li class="mb-2 text-white-50"><i class="fas fa-phone me-2"></i> +1 234 567 890</li>
                        <li class="mb-2 text-white-50"><i class="fas fa-map-marker-alt me-2"></i> 123 Job Street, Employment City, 12345</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="bg-darker py-3">
            <div class="container text-center">
                <p class="mb-0 text-white">&copy; <?php echo date('Y'); ?> JobPortal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Custom CSS for footer links -->
    <style>
        footer a.hover-white:hover {
            color: #ffffff !important;
            text-decoration: underline !important;
            transition: all 0.3s ease;
        }
        
        footer .social-links a:hover {
            opacity: 0.8;
            transform: translateY(-3px);
            transition: all 0.3s ease;
        }
        
        footer h5 {
            position: relative;
            padding-bottom: 10px;
        }
        
        footer h5:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: #3490dc;
        }
        
        .bg-darker {
            background-color: rgba(0, 0, 0, 0.2);
        }
    </style>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for some Bootstrap features) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js" integrity="sha512-ykZ1QQr0Jy/4ZkvKuqWn4iF3lqPZyij9iRv6sGqLRdTPkY69YX6+7wvVGmsdBbiIfN/8OdsI7HABjvEok6ZopQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Replace default alerts with SweetAlert2
        window.alert = function(message) {
            Swal.fire({
                text: message,
                confirmButtonColor: '#0d6efd'
            });
        };

        // Add loading spinner to buttons when clicked
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.form && this.form.checkValidity()) {
                    // Store the original button text
                    this.setAttribute('data-original-text', this.innerHTML);
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                    this.disabled = true;
                    
                    // Set a timeout to reset the button if the form doesn't redirect
                    setTimeout(() => {
                        if (document.body.contains(this)) {
                            this.innerHTML = this.getAttribute('data-original-text');
                            this.disabled = false;
                        }
                    }, 5000); // 5 seconds timeout
                }
            });
        });

        // Animate elements on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate-on-scroll');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                if (elementTop < windowHeight - 50) {
                    element.classList.add('animate-fade-in');
                }
            });
        }

        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Initial check
    </script>

    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>
