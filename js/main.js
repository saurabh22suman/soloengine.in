document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Replace the print button functionality with enhanced version
    const printButtons = document.querySelectorAll('button[onclick="window.print()"], .btn-print');
    if (printButtons.length > 0) {
        printButtons.forEach(button => {
            // Remove the inline onclick attribute to prevent double execution
            button.removeAttribute('onclick');
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                handlePrint();
            });
        });
    }
    
    // Add PDF download functionality
    const downloadButton = document.getElementById('download-resume-btn');
    if (downloadButton) {
        downloadButton.addEventListener('click', function(e) {
            e.preventDefault();
            generatePDF();
        });
    }
    
    function generatePDF() {
        // Show loading indicator
        const loadingMsg = document.createElement('div');
        loadingMsg.className = 'print-message';
        loadingMsg.innerHTML = '<p>Generating PDF, please wait...</p>';
        loadingMsg.style.position = 'fixed';
        loadingMsg.style.top = '0';
        loadingMsg.style.left = '0';
        loadingMsg.style.width = '100%';
        loadingMsg.style.padding = '10px';
        loadingMsg.style.backgroundColor = '#007bff';
        loadingMsg.style.color = '#fff';
        loadingMsg.style.textAlign = 'center';
        loadingMsg.style.zIndex = '9999';
        loadingMsg.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        document.body.appendChild(loadingMsg);
        
        // Generate a timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        // Redirect to the PDF generation script
        window.location.href = `generate-pdf-wk.php?t=${timestamp}`;
        
        // Remove the loading message after a delay
        setTimeout(() => {
            if (document.body.contains(loadingMsg)) {
                document.body.removeChild(loadingMsg);
            }
        }, 3000);
    }
    
    function handlePrint() {
        // Add a class to body for print preparation
        document.body.classList.add('preparing-for-print');
        
        // Make sure all sections are visible
        const allSections = document.querySelectorAll('section');
        allSections.forEach(section => {
            section.classList.add('print-visible');
            
            // Ensure all cards within this section are visible
            const cards = section.querySelectorAll('.card');
            cards.forEach(card => {
                card.classList.add('print-visible');
            });
        });
        
        // Optional: Show a print preparation message
        const printMsg = document.createElement('div');
        printMsg.className = 'print-message';
        printMsg.innerHTML = '<p>Preparing document for printing...</p>';
        printMsg.style.position = 'fixed';
        printMsg.style.top = '0';
        printMsg.style.left = '0';
        printMsg.style.width = '100%';
        printMsg.style.padding = '10px';
        printMsg.style.backgroundColor = '#f8f9fa';
        printMsg.style.textAlign = 'center';
        printMsg.style.zIndex = '9999';
        printMsg.style.borderBottom = '1px solid #dee2e6';
        document.body.appendChild(printMsg);
        
        // Allow time for styles to apply and resources to load
        setTimeout(() => {
            // Remove the message before printing
            document.body.removeChild(printMsg);
            
            // Execute print
            window.print();
            
            // Remove preparing class after printing dialog is closed
            setTimeout(() => {
                document.body.classList.remove('preparing-for-print');
                
                // Reset any temporary classes we added
                allSections.forEach(section => {
                    section.classList.remove('print-visible');
                });
            }, 1000);
        }, 300);
    }
    
    // Listen for the beforeprint and afterprint events
    window.addEventListener('beforeprint', () => {
        document.body.classList.add('is-printing');
    });
    
    window.addEventListener('afterprint', () => {
        document.body.classList.remove('is-printing');
    });
    
    // Smooth scrolling for all links with header offset adjustment
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Get header height to use as offset
                const headerHeight = document.querySelector('header').offsetHeight;
                
                // Get the element's position relative to the viewport
                const elementPosition = targetElement.getBoundingClientRect().top;
                
                // Get the current scroll position
                const offsetPosition = elementPosition + window.pageYOffset - headerHeight;
                
                // Scroll to the element with offset
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Close the navbar collapse if it's open (for mobile)
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            }
        });
    });

    // Intersection Observer for scroll animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all sections
    document.querySelectorAll('section').forEach(section => {
        section.classList.add('animate-on-scroll');
        observer.observe(section);
    });

    // Animate skill bars when they come into view
    const skillObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.width = entry.target.getAttribute('aria-valuenow') + '%';
                skillObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.progress-bar').forEach(bar => {
        bar.style.width = '0%';
        skillObserver.observe(bar);
    });

    // Add hover effect to project cards
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add active state to navigation links
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('a[href^="#"]');
    
    window.addEventListener('scroll', () => {
        let current = '';
        const headerHeight = document.querySelector('header').offsetHeight;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - headerHeight - 5)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').slice(1) === current) {
                link.classList.add('active');
            }
        });
    });

    // Add typing effect to profile title
    const title = document.querySelector('.card-title');
    if (title) {
        const text = title.textContent;
        title.textContent = '';
        let i = 0;
        
        function typeWriter() {
            if (i < text.length) {
                title.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        }
        
        typeWriter();
    }

    // Add parallax effect to header
    window.addEventListener('scroll', () => {
        const header = document.querySelector('header');
        if (header) {
            const scrolled = window.pageYOffset;
            header.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });
}); 