/**
 * Enhanced Sidebar Interactive Effects
 */
$(document).ready(function() {
    // Add ripple effect to sidebar menu items
    function createRipple(event) {
        const button = event.currentTarget;
        
        const circle = document.createElement("span");
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        
        // Set ripple properties
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - (button.getBoundingClientRect().left + radius)}px`;
        circle.style.top = `${event.clientY - (button.getBoundingClientRect().top + radius)}px`;
        circle.classList.add("ripple");
        
        // Remove existing ripples
        const ripple = button.getElementsByClassName("ripple")[0];
        if (ripple) {
            ripple.remove();
        }
        
        // Add ripple to the button
        button.appendChild(circle);
        
        // Remove ripple after animation completes
        setTimeout(() => {
            if (circle) {
                circle.remove();
            }
        }, 600);
    }
    
    // Apply ripple effect to nav links
    const navLinks = document.querySelectorAll('.custom-sidebar-menu .nav-link');
    navLinks.forEach(button => {
        button.addEventListener('click', createRipple);
    });
    
    // Add hover effect for icons
    $('.custom-sidebar-menu .nav-link').hover(
        function() {
            $(this).find('i').addClass('icon-pulse');
        },
        function() {
            $(this).find('i').removeClass('icon-pulse');
        }
    );
    
    // Add active class to current page nav item
    const currentPath = window.location.pathname;
    $('.custom-sidebar-menu .nav-link').each(function() {
        const linkHref = $(this).attr('href');
        if (linkHref && linkHref !== '#' && currentPath.includes(linkHref.split('?')[0])) {
            $(this).addClass('active');
            $(this).parents('.nav-item').addClass('menu-open');
            $(this).parents('.nav-treeview').show();
        }
    });
    
    // Smooth collapse/expand for treeview menus
    $('.custom-sidebar-menu .has-treeview > .nav-link').on('click', function() {
        const $navItem = $(this).parent();
        const $navTreeview = $navItem.find('.nav-treeview').first();
        
        if ($navItem.hasClass('menu-open')) {
            $navTreeview.slideUp(300, function() {
                $navItem.removeClass('menu-open');
            });
        } else {
            $navTreeview.slideDown(300, function() {
                $navItem.addClass('menu-open');
            });
        }
    });
    
    // Add CSS for ripple effect if not already in the custom CSS
    if (!document.getElementById('ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
            .nav-link {
                position: relative;
                overflow: hidden;
            }
            .ripple {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.4);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            }
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            .icon-pulse {
                animation: icon-pulse 0.5s ease;
            }
            @keyframes icon-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }
});
