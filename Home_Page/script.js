// --- DOM Elements ---
const colorOptionsContainer = document.getElementById("color-options-section");
const layoutOptionsContainer = document.getElementById("layout-options-section");
const sidesOptionsContainer = document.getElementById("sides-options-section");
const pagesOptionsContainer = document.getElementById("pages-options-section");
const customPageRangeInput = document.getElementById("customPageRange");
const copiesOptionsContainer = document.getElementById("copies-options-section");
const copiesInput = document.getElementById("copiesInput");
const decreaseCopiesBtn = document.getElementById("decreaseCopies");
const increaseCopiesBtn = document.getElementById("increaseCopies");

const dropArea = document.getElementById("dropArea");
const browseBtn = document.getElementById("browseBtn");
const fileInput = document.getElementById("fileInput");
const progressArea = document.getElementById("progressArea");
const uploadedArea = document.getElementById("uploadedArea");

const summaryColor = document.getElementById("summaryColor");
const summaryLayout = document.getElementById("summaryLayout");
const summarySides = document.getElementById("summarySides");
const summaryPages = document.getElementById("summaryPages");
const summaryCopies = document.getElementById("summaryCopies");
const summaryFiles = document.getElementById("summaryFiles");
const printBtn = document.getElementById("printBtn");

// --- State Variables ---
let selectedColor = { value: "bw", label: "Black & White" };
let selectedLayout = { value: "portrait", label: "Portrait" };
let selectedSides = { value: "one-sided", label: "One-sided" };
let selectedPagesOption = { value: "all", label: "All" };
let customPagesValue = "";
let numberOfCopies = 1;
let uploadedFiles = []; // Will store { id: string, file: File } objects

// --- Initialization ---
document.addEventListener("DOMContentLoaded", () => {
    initializeOptions();
    updateSummary(); // Initial summary update
    setupEventListeners();
    printBtn.disabled = true; // Disable print button initially
});

function initializeOptions() {
    // Pre-select default radio buttons based on initial state
    const defaultColorInput = document.querySelector(`input[name="color"][value="${selectedColor.value}"]`);
    if (defaultColorInput) defaultColorInput.checked = true;
    // Update label from checked input just in case HTML default differs
    selectedColor.label = defaultColorInput.closest(".radio-button").querySelector(".radio-label-text").textContent;

    const defaultLayoutInput = document.querySelector(`input[name="layout"][value="${selectedLayout.value}"]`);
    if (defaultLayoutInput) defaultLayoutInput.checked = true;
    selectedLayout.label = defaultLayoutInput.closest(".radio-button").querySelector(".radio-label-text").textContent;

    const defaultSidesInput = document.querySelector(`input[name="sides"][value="${selectedSides.value}"]`);
    if (defaultSidesInput) defaultSidesInput.checked = true;
    selectedSides.label = defaultSidesInput.closest(".radio-button").querySelector(".radio-label-text").textContent;

    const defaultPagesInput = document.querySelector(`input[name="pages"][value="${selectedPagesOption.value}"]`);
    if (defaultPagesInput) defaultPagesInput.checked = true;
    selectedPagesOption.label = defaultPagesInput.closest(".radio-button").querySelector(".radio-label-text")?.textContent || "Custom"; // Handle custom case
    // Handle initial state of custom input
    if (selectedPagesOption.value === 'custom') {
        customPageRangeInput.disabled = false;
    } else {
        customPageRangeInput.disabled = true;
    }
    
    // Set initial copies value in input
    copiesInput.value = numberOfCopies;
}

// --- Event Listeners Setup ---
function setupEventListeners() {
    // Color Selection (Step 003)
    colorOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "color") {
            selectedColor = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
        }
    });

    // Layout Selection (Step 004)
    layoutOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "layout") {
            selectedLayout = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
        }
    });

    // Sides Selection (Step 004)
    sidesOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "sides") {
            selectedSides = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
        }
    });

    // Pages Selection (Step 005)
    pagesOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "pages") {
            const value = event.target.value;
            selectedPagesOption = {
                value: value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text")?.textContent || "Custom"
            };
            // Enable/disable custom input
            if (value === "custom") {
                customPageRangeInput.disabled = false;
                customPageRangeInput.focus();
            } else {
                customPageRangeInput.disabled = true;
                customPagesValue = ""; // Clear custom value if another option is selected
                customPageRangeInput.value = "";
            }
            updateSummary();
        }
    });

    // Custom Page Range Input (Step 005)
    customPageRangeInput.addEventListener("input", (event) => {
        if (!event.target.disabled) {
            customPagesValue = event.target.value.trim();
            // Basic validation could be added here (e.g., check format)
            updateSummary(); // Update summary to reflect custom input potentially
        }
    });

    // Copies Input (Step 005)
    decreaseCopiesBtn.addEventListener("click", () => {
        const minCopies = parseInt(copiesInput.min) || 1;
        if (numberOfCopies > minCopies) {
            numberOfCopies--;
            copiesInput.value = numberOfCopies;
            updateSummary();
        }
    });

    increaseCopiesBtn.addEventListener("click", () => {
        const maxCopies = parseInt(copiesInput.max) || 999;
        if (numberOfCopies < maxCopies) {
            numberOfCopies++;
            copiesInput.value = numberOfCopies;
            updateSummary();
        }
    });

    copiesInput.addEventListener("change", () => {
        const minCopies = parseInt(copiesInput.min) || 1;
        const maxCopies = parseInt(copiesInput.max) || 999;
        let value = parseInt(copiesInput.value);

        if (isNaN(value) || value < minCopies) {
            value = minCopies;
        } else if (value > maxCopies) {
            value = maxCopies;
        }
        numberOfCopies = value;
        copiesInput.value = numberOfCopies; // Ensure input reflects validated value
        updateSummary();
    });

    // File Upload Logic (Step 006)
    browseBtn.addEventListener("click", () => fileInput.click());

    fileInput.addEventListener("change", (event) => {
        handleFiles(event.target.files);
        // Reset file input to allow uploading the same file again if needed
        event.target.value = null;
    });

    // Drag and Drop
    dropArea.addEventListener("dragover", (event) => {
        event.preventDefault();
        dropArea.classList.add("active");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("active");
    });

    dropArea.addEventListener("drop", (event) => {
        event.preventDefault();
        dropArea.classList.remove("active");
        handleFiles(event.dataTransfer.files);
    });

    // Print Button Logic (Step 007)
    printBtn.addEventListener("click", () => {
        if (uploadedFiles.length === 0) {
            alert("Please upload at least one file before submitting.");
            return;
        }

        let pagesToPrint = selectedPagesOption.label;
        if (selectedPagesOption.value === "custom") {
            if (customPagesValue) {
                // Optional: Add validation for the customPagesValue format here
                pagesToPrint = `Custom: ${customPagesValue}`;
            } else {
                alert("Please enter a custom page range or select another Pages option.");
                customPageRangeInput.focus();
                return; // Stop submission if custom is selected but empty
            }
        }

        // Construct the summary message
        const summaryMessage = `Print Job Submitted:\n--------------------------\nColor: ${selectedColor.label}\nLayout: ${selectedLayout.label}\nSides: ${selectedSides.label}\nPages: ${pagesToPrint}\nCopies: ${numberOfCopies}\nFiles: ${uploadedFiles.map(f => f.file.name).join(", ")}\n--------------------------`;

        alert(summaryMessage);

        // Here you would typically send the data to a server
        // e.g., sendOrderToServer({ color: selectedColor.value, layout: selectedLayout.value, ... });
        
        // Optional: Reset form after submission
        // resetForm(); 
    });
}
// --- Summary Update ---
function updateSummary() {
    summaryColor.textContent = selectedColor.label;
    summaryLayout.textContent = selectedLayout.label; // Step 004
    summarySides.textContent = selectedSides.label; // Step 004
    
    let pagesSummaryText = selectedPagesOption.label;
    if (selectedPagesOption.value === 'custom' && customPagesValue) {
        pagesSummaryText = `Custom: ${customPagesValue}`;
    } else if (selectedPagesOption.value === 'custom' && !customPagesValue) {
        pagesSummaryText = "Custom (Specify range)"; // Prompt user
    }
    summaryPages.textContent = pagesSummaryText; // Step 005
    
    summaryCopies.textContent = numberOfCopies; // Step 005
    summaryFiles.textContent = `${uploadedFiles.length} ${uploadedFiles.length === 1 ? "file" : "files"}`; // Step 006

    // Enable/disable print button based on file upload (Step 006)
    printBtn.disabled = uploadedFiles.length === 0;
}

// --- File Handling Logic (Step 006) ---
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB limit
const ALLOWED_EXTENSIONS = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];

function handleFiles(files) {
    progressArea.style.display = "block"; // Show progress area
    uploadedArea.style.display = "block"; // Ensure uploaded area is visible

    for (let file of files) {
        // Validation
        const extension = file.name.split(".").pop().toLowerCase();
        if (!ALLOWED_EXTENSIONS.includes(extension)) {
            alert(`Unsupported file type: ${file.name}. Allowed: ${ALLOWED_EXTENSIONS.join(", ")}`);
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            alert(`File size too large: ${file.name}. Max: ${formatFileSize(MAX_FILE_SIZE)}`);
            continue;
        }
        if (uploadedFiles.some(f => f.file.name === file.name)) {
             alert(`File ${file.name} is already uploaded.`);
             continue;
        }

        uploadFile(file);
    }
    // Hide progress area if no valid files were processed
    if (progressArea.children.length === 0) {
        progressArea.style.display = "none";
    }
}

function uploadFile(file) {
    const fileId = `file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

    const progressHTML = `
        <div class="row" id="${fileId}">
            <div class="file-icon"><i class="fas ${getFileIcon(file.name)}"></i></div>
            <div class="content">
                <div class="details">
                    <span class="name">${file.name}</span>
                    <span class="percent">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: 0%"></div>
                </div>
            </div>
            <button class="remove-file-btn" data-fileid="${fileId}" aria-label="Remove file">&times;</button>
        </div>`;
    progressArea.insertAdjacentHTML("beforeend", progressHTML);

    const progressRow = document.getElementById(fileId);
    const progressBar = progressRow.querySelector(".progress");
    const progressPercent = progressRow.querySelector(".percent");
    const removeBtn = progressRow.querySelector(".remove-file-btn");

    // Remove button during progress
    removeBtn.onclick = () => removeFile(fileId, file.name, true);

    // Simulate upload
    let width = 0;
    const interval = setInterval(() => {
        width += 5;
        if (width >= 100) {
            clearInterval(interval);
            // Check if row still exists before updating/moving
            if (document.getElementById(fileId)) {
                progressBar.style.width = "100%";
                progressPercent.textContent = "100%";
                setTimeout(() => {
                    if (document.getElementById(fileId)) {
                        progressRow.remove();
                        addFileToUploadedList(file, fileId);
                    }
                    if (progressArea.children.length === 0) {
                        progressArea.style.display = "none";
                    }
                }, 300);
            }
        } else {
            if (document.getElementById(fileId)) {
                progressBar.style.width = `${width}%`;
                progressPercent.textContent = `${width}%`;
            }
        }
    }, 50);
}

function addFileToUploadedList(file, fileId) {
    uploadedFiles.push({ id: fileId, file: file });

    const uploadedHTML = `
        <div class="row" id="${fileId}">
            <div class="file-icon"><i class="fas ${getFileIcon(file.name)}"></i></div>
            <div class="content">
                <div class="details">
                    <span class="name">${file.name}</span>
                    <span class="size">${formatFileSize(file.size)}</span>
                </div>
            </div>
             <button class="remove-file-btn" data-fileid="${fileId}" aria-label="Remove file">&times;</button>
        </div>`;
    uploadedArea.insertAdjacentHTML("beforeend", uploadedHTML);

    // Remove button in uploaded list
    const uploadedRow = document.getElementById(fileId);
    const removeBtn = uploadedRow.querySelector(".remove-file-btn");
    removeBtn.onclick = () => removeFile(fileId, file.name);

    updateSummary();
}

function removeFile(fileId, fileName, duringProgress = false) {
    const rowElement = document.getElementById(fileId);
    if (rowElement) {
        rowElement.remove();
    }

    uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);

    if (duringProgress && progressArea.children.length === 0) {
         progressArea.style.display = "none";
    }
    
    // Keep uploaded area visible even if empty, just show no files

    console.log(`Removed file: ${fileName}`);
    updateSummary();
}

// --- Helper Functions ---
function getFileIcon(fileName) {
    const extension = fileName.split(".").pop().toLowerCase();
    const iconMap = {
        "pdf": "fa-file-pdf",
        "doc": "fa-file-word",
        "docx": "fa-file-word",
        "jpg": "fa-file-image",
        "jpeg": "fa-file-image",
        "png": "fa-file-image",
        "xls": "fa-file-excel",
        "xlsx": "fa-file-excel",
        "ppt": "fa-file-powerpoint",
        "pptx": "fa-file-powerpoint",
        "zip": "fa-file-archive",
        "rar": "fa-file-archive",
        "txt": "fa-file-alt",
    };
    return iconMap[extension] || "fa-file"; // Default icon
}

function formatFileSize(bytes) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

            // Sidebar functionality
            const menuToggle = document.querySelector('.menu-toggle');
            const closeSidebar = document.querySelector('.close-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const sidebarLinks = document.querySelectorAll('.sidebar-nav a');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
            }

            function closeSidebarFunc() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 300);
                document.body.style.overflow = 'auto';
            }

            menuToggle.addEventListener('click', toggleSidebar);
            closeSidebar.addEventListener('click', closeSidebarFunc);
            overlay.addEventListener('click', closeSidebarFunc);

            // Close sidebar when a link is clicked
            sidebarLinks.forEach(link => {
                link.addEventListener('click', closeSidebarFunc);
            });

            // Touch support for sidebar
            let touchStartX = 0;
            let touchEndX = 0;

            sidebar.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            });

            sidebar.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    closeSidebarFunc();
                }
            });

            // Smooth scrolling for navigation links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Timeline scroll animations
            $(document).ready(function () {
                function isElementInViewport(el) {
                    var rect = el[0].getBoundingClientRect();
                    return (
                        rect.top >= 0 &&
                        rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.8
                    );
                }

                function handleScroll() {
                    $('.timeline-item').each(function (index) {
                        if (isElementInViewport($(this)) && !$(this).hasClass('visible')) {
                            setTimeout(() => {
                                $(this).addClass('visible');
                            }, index * 200); // Staggered animation
                        }
                    });
                }

                handleScroll();
                $(window).on('scroll', handleScroll);
            });
            
            // DOM elements
            const digitInputs = document.querySelectorAll('.digit-input');
            const verifyBtn = document.getElementById('verify-btn');
            const backBtn = document.getElementById('back-btn');
            const feedbackMessage = document.getElementById('feedback-message');
    
            // Function to handle input navigation
            function setupInputNavigation() {
                digitInputs.forEach((input, index) => {
                    // Allow only numbers
                    input.addEventListener('keypress', (e) => {
                        if (!/[0-9]/.test(e.key)) {
                            e.preventDefault();
                        }
                    });
    
                    // Move to next input when a digit is entered
                    input.addEventListener('input', (e) => {
                        if (e.target.value !== '') {
                            // Move to next input
                            if (index < digitInputs.length - 1) {
                                digitInputs[index + 1].focus();
                            } else {
                                // Last input, can verify
                                verifyBtn.focus();
                            }
                        }
                    });
    
                    // Handle backspace
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace') {
                            if (e.target.value === '' && index > 0) {
                                digitInputs[index - 1].focus();
                            }
                        }
                    });
                });
            }
    
            // Function to check if entered code matches saved code
            function checkMatchingCode() {
                const savedNumber = localStorage.getItem('randomNumber') || '';
                let enteredNumber = '';
    
                digitInputs.forEach(input => {
                    enteredNumber += input.value;
                });
    
                // Clear any existing classes
                feedbackMessage.classList.remove('success', 'error');
    
                if (enteredNumber.length < 6) {
                    // Not complete
                    feedbackMessage.textContent = 'Please enter all six digits';
                    feedbackMessage.classList.add('error');
                    feedbackMessage.style.display = 'block';
                } else if (enteredNumber === savedNumber) {
                    // Match
                    feedbackMessage.textContent = 'Number matches! Printing now!';
                    feedbackMessage.classList.add('success');
                    feedbackMessage.style.display = 'block';
                } else {
                    // No match
                    feedbackMessage.textContent = 'Number does not match the saved number!';
                    feedbackMessage.classList.add('error');
                    feedbackMessage.style.display = 'block';
                }
    
                setTimeout(() => {
                    if (enteredNumber === savedNumber) {
                        // You could add additional actions for success here
                    }
                }, 1000);
            }
    
            // Function to navigate back to main page
            function navigateToMainPage() {
                window.location.href = 'index.html';
            }
    
            // Function to prefill inputs if there's a saved number
            function prefillInputs() {
                const savedNumber = localStorage.getItem('randomNumber');
                if (savedNumber && savedNumber.length === 6) {
                    for (let i = 0; i < 6; i++) {
                        digitInputs[i].value = savedNumber.charAt(i);
                    }
                }
            }
    
            // Initialize
            function init() {
                // Set up input navigation
                setupInputNavigation();
    
                // Set up event listeners
                verifyBtn.addEventListener('click', checkMatchingCode);
                backBtn.addEventListener('click', navigateToMainPage);
    
                // Prefill inputs
                prefillInputs();
            }
    
            // Run initialization when DOM is loaded
            document.addEventListener('DOMContentLoaded', init);

