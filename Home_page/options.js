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
const summaryPageCount = document.getElementById("summaryPageCount");
const printBtn = document.getElementById("printBtn");

// --- State Variables ---
let selectedColor = { value: "bw", label: "Black & White" };
let selectedLayout = { value: "portrait", label: "Portrait" };
let selectedSides = { value: "one-sided", label: "One-sided" };
let selectedPagesOption = { value: "all", label: "All" };
let numberOfCopies = 1;
let uploadedFiles = []; // Will store { id: string, file: File, pageCount: number, options: Object } objects
let totalPageCount = 0;
let currentJobId = null; // Added for DB tracking
let totalCost = 0; // Variable to store the calculated cost

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
    // Color Selection
    colorOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "color") {
            selectedColor = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
            updateCostDisplay();
        }
    });

    // Layout Selection
    layoutOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "layout") {
            selectedLayout = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
        }
    });

    // Sides Selection
    sidesOptionsContainer.addEventListener("change", (event) => {
        if (event.target.name === "sides") {
            selectedSides = {
                value: event.target.value,
                label: event.target.closest(".radio-button").querySelector(".radio-label-text").textContent
            };
            updateSummary();
            updateCostDisplay();
        }
    });

    // Pages Selection
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
                customPageRangeInput.value = "";
            }
            updateSummary();
        }
    });

    // Custom Page Range Input
    customPageRangeInput.addEventListener("input", (event) => {
        if (!event.target.disabled) {
            updateSummary();
        }
    });

    // Copies Input Controls
    decreaseCopiesBtn.addEventListener("click", () => {
        const minCopies = parseInt(copiesInput.min) || 1;
        if (numberOfCopies > minCopies) {
            numberOfCopies--;
            copiesInput.value = numberOfCopies;
            updateSummary();
            updateCostDisplay();
        }
    });

    increaseCopiesBtn.addEventListener("click", () => {
        const maxCopies = parseInt(copiesInput.max) || 999;
        if (numberOfCopies < maxCopies) {
            numberOfCopies++;
            copiesInput.value = numberOfCopies;
            updateSummary();
            updateCostDisplay();
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
        updateCostDisplay();
    });

    // File Upload Logic
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

    // Print Button Logic
    printBtn.addEventListener("click", () => {
        if (uploadedFiles.length === 0) {
            alert("الرجاء رفع ملف واحد على الأقل قبل الإرسال.");
            return;
        }

        // Calculate cost before showing modal
        calculateCost();

        // Show print confirmation modal
        const modal = document.getElementById('printConfirmModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    });

    // Setup modal button events
    const cancelBtn = document.getElementById('cancelBtn');
    const okBtn = document.getElementById('okBtn');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('printConfirmModal').style.display = 'none';
        });
    }

    if (okBtn) {
        okBtn.addEventListener('click', () => {
            document.getElementById('printConfirmModal').style.display = 'none';
            
            // Send print jobs to the server
            processPrintJob();
        });
    }
}

// --- Summary Update ---
function updateSummary() {
    if (summaryColor) summaryColor.textContent = selectedColor.label;
    if (summaryLayout) summaryLayout.textContent = selectedLayout.label;
    if (summarySides) summarySides.textContent = selectedSides.label;
    
    let pagesSummaryText = selectedPagesOption.label;
    if (selectedPagesOption.value === 'custom' && customPageRangeInput.value) {
        pagesSummaryText = `مخصص: ${customPageRangeInput.value}`;
    } else if (selectedPagesOption.value === 'custom' && !customPageRangeInput.value) {
        pagesSummaryText = "مخصص (حدد النطاق)"; // Prompt user
    }
    if (summaryPages) summaryPages.textContent = pagesSummaryText;
    
    if (summaryCopies) summaryCopies.textContent = numberOfCopies;
    if (summaryFiles) summaryFiles.textContent = `${uploadedFiles.length} ${uploadedFiles.length === 1 ? "File" : "Files"}`;
    if (summaryPageCount) summaryPageCount.textContent = totalPageCount * numberOfCopies;

    // Enable/disable print button based on file upload
    if (printBtn) printBtn.disabled = uploadedFiles.length === 0;
}

// --- File Handling Logic ---
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB limit
const ALLOWED_EXTENSIONS = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];

function handleFiles(files) {
    if (!progressArea) return;
    
    progressArea.style.display = "block"; // Show progress area
    uploadedArea.style.display = "block"; // Ensure uploaded area is visible

    for (let file of files) {
        // Validation
        const extension = file.name.split(".").pop().toLowerCase();
        if (!ALLOWED_EXTENSIONS.includes(extension)) {
            alert(`نوع ملف غير مدعوم: ${file.name}. الأنواع المسموح بها: ${ALLOWED_EXTENSIONS.join(", ")}`);
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            alert(`حجم الملف كبير جدًا: ${file.name}. الحد الأقصى: ${formatFileSize(MAX_FILE_SIZE)}`);
            continue;
        }
        if (uploadedFiles.some(f => f.file.name === file.name)) {
             alert(`الملف ${file.name} تم رفعه بالفعل.`);
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
    if (!file) return;

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

    // Create FormData for server upload
    const formData = new FormData();
    formData.append('file', file);
    
    // AJAX request to upload file to server
    const xhr = new XMLHttpRequest();
    // تعديل المسار ليتناسب مع هيكل موقعك - هذا هو أحد المشاكل المحتملة
    xhr.open('POST', '../BackEnd/PHP-pages/upload.php', true);
    
    xhr.upload.addEventListener('progress', ({loaded, total}) => {
        const fileLoaded = Math.floor((loaded / total) * 100);
        if (progressBar && progressPercent) {
            progressBar.style.width = fileLoaded + '%';
            progressPercent.textContent = fileLoaded + '%';
        }
    });
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    // Count pages after successful upload
                    countPDFPages(file).then(pageCount => {
                        completedFileUpload(file, fileId, pageCount, response);
                    }).catch(error => {
                        console.error("Error counting pages:", error);
                        completedFileUpload(file, fileId, estimatePageCount(file.type, file.size), response);
                    });
                } else {
                    // Handle upload failure
                    alert(response.message || 'فشل الرفع. الرجاء المحاولة مرة أخرى.');
                    if (progressRow) progressRow.remove();
                }
            } catch (e) {
                console.error('Error parsing server response:', e);
                alert('حدث خطأ ما. الرجاء المحاولة مرة أخرى.');
                if (progressRow) progressRow.remove();
            }
        }
    };
    
    xhr.send(formData);
}

// Function to remove a file from the list
function removeFile(fileId, fileName, isUploading = false) {
    // Find the file in our array
    const fileIndex = uploadedFiles.findIndex(file => file.id === fileId);
    
    if (isUploading) {
        // If the file is still uploading, remove it from progress area
        const progressRow = document.getElementById(fileId);
        if (progressRow) progressRow.remove();
    } else if (fileIndex !== -1) {
        // Remove from the uploaded files array
        const removedFile = uploadedFiles.splice(fileIndex, 1)[0];
        
        // Update total page count
        totalPageCount -= parseInt(removedFile.pageCount) || 0;
        
        // Remove from the UI
        const uploadedRow = document.getElementById(fileId);
        if (uploadedRow) uploadedRow.remove();
        
        // If the file was successfully uploaded to server, send delete request
        if (removedFile.path && removedFile.serverName) {
            // Send request to server to delete the file
           
            fetch('../BackEnd/PHP-pages/delete_file.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    file_path: removedFile.path,
                    file_name: removedFile.serverName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.warn('File removed from UI but server deletion failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting file from server:', error);
            });
        }
        
        // Update summary to reflect changes
        updateSummary();
        
        // Hide uploaded area if no files left
        if (uploadedFiles.length === 0 && uploadedArea) {
            uploadedArea.style.display = "none";
        }
    }
    
    // Update cost display after file removal
    updateCostDisplay();
}

// Function to calculate cost via AJAX
function calculateCost() {
    if (uploadedFiles.length === 0) return;
    
    // Get the most recent file's data
    const file = uploadedFiles[0];
    
    // Prepare data for cost calculation
    const data = new FormData();
    data.append('action', 'calculate_cost');
    data.append('color', selectedColor.value);
    data.append('sides', selectedSides.value);
    data.append('page_count', file.pageCount);
    data.append('copies', numberOfCopies);
    
    // AJAX request to calculate cost
    // تعديل المسار ليتناسب مع هيكل موقعك
    fetch('../BackEnd/PHP-pages/upload.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            totalCost = data.cost;
            // Update cost in the modal
            const costElement = document.getElementById('cost');
            if (costElement) {
                costElement.textContent = totalCost.toFixed(2);
            }
        } else {
            console.error('Error calculating cost:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Update cost when copies change or file is uploaded
function updateCostDisplay() {
    if (uploadedFiles.length > 0) {
        calculateCost();
    }
}

// Function to process print job with payment
function processPrintJob() {
    if (uploadedFiles.length === 0) {
        alert('الرجاء رفع ملف واحد على الأقل قبل الإرسال.');
        return;
    }
    
    // Use the most recent file's data
    const file = uploadedFiles[0];
    
    // Prepare job data including temp file path
    const formData = new FormData();
    formData.append('action', 'process_print');
    formData.append('temp_file_path', file.path || '');
    formData.append('original_file_name', file.file.name);
    formData.append('page_count', file.pageCount);
    formData.append('copies', numberOfCopies);
    formData.append('color', selectedColor.value);
    formData.append('sides', selectedSides.value);
    formData.append('layout', selectedLayout.value);
    formData.append('pages', selectedPagesOption.value);
    formData.append('page_range', selectedPagesOption.value === 'custom' ? customPageRangeInput.value : '');
    
    // AJAX request to process print job with payment
    // تعديل المسار ليتناسب مع هيكل موقعك
    fetch('../BackEnd/PHP-pages/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`تم تقديم مهمة الطباعة بنجاح!\nالتكلفة: ${data.cost} AITP\nالرصيد المتبقي: ${data.remaining_balance} AITP`);
            
            // Reset form after successful submission
            resetForm();
        } else {
            alert(data.message || 'فشل في معالجة مهمة الطباعة. الرجاء المحاولة مرة أخرى.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ ما. الرجاء المحاولة مرة أخرى.');
    });
}

function completedFileUpload(file, fileId, pageCount, serverResponse = null) {
    const progressRow = document.getElementById(fileId);
    if (!progressRow) return; // The row might have been removed

    // Ensure the progress bar is complete
    const progressBar = progressRow.querySelector(".progress");
    const progressPercent = progressRow.querySelector(".percent");
    
    progressBar.style.width = "100%";
    progressPercent.textContent = "100%";
    
    setTimeout(() => {
        if (document.getElementById(fileId)) {
            progressRow.remove();
            
            // Use server data if available
            const fileData = {
                id: fileId,
                file: file,
                pageCount: pageCount,
                path: serverResponse ? serverResponse.file_path : null,
                serverName: serverResponse ? serverResponse.file_name : null,
                options: {
                    color: selectedColor.value,
                    layout: selectedLayout.value,
                    sides: selectedSides.value,
                    pages: selectedPagesOption.value === 'custom' ? customPageRangeInput.value : selectedPagesOption.value,
                    copies: numberOfCopies
                }
            };
            
            addFileToUploadedList(fileData);
            
            // Calculate cost when file is uploaded
            updateCostDisplay();
        }
        if (progressArea.children.length === 0) {
            progressArea.style.display = "none";
        }
    }, 300);
}

function addFileToUploadedList(fileData) {
    // Store file in the global array
    uploadedFiles.push(fileData);

    // Update total page count
    totalPageCount += parseInt(fileData.pageCount) || 0;

    const uploadedHTML = `
        <div class="row" id="${fileData.id}">
            <div class="file-icon"><i class="fas ${getFileIcon(fileData.file.name)}"></i></div>
            <div class="content">
                <div class="details">
                    <span class="name">${fileData.file.name}</span>
                    <span class="size">${formatFileSize(fileData.file.size)} - ${fileData.pageCount} Pages</span>
                </div>
            </div>
            <button class="remove-file-btn" data-fileid="${fileData.id}" aria-label="Remove file">&times;</button>
        </div>`;
    uploadedArea.insertAdjacentHTML("beforeend", uploadedHTML);

    // Remove button in uploaded list
    const uploadedRow = document.getElementById(fileData.id);
    const removeBtn = uploadedRow.querySelector(".remove-file-btn");
    removeBtn.onclick = () => removeFile(fileData.id, fileData.file.name);

    updateSummary();
}

// --- PDF Page Counter ---
function countPDFPages(file) {
    return new Promise((resolve, reject) => {
        if (file.type !== 'application/pdf') {
            // For non-PDF files, estimate page count
            resolve(estimatePageCount(file.type, file.size));
            return;
        }
        
        // For real implementation, check if there's a page counting service
        // If there is no page counting service available, use the estimate
        const formData = new FormData();
        formData.append("pdf_file", file);
        
        // Try to use the server-side page counter if available
        // تعديل المسار ليتناسب مع هيكل موقعك
        fetch("../BackEnd/PHP-pages/calculate/count_pages.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resolve(data.pages);
            } else {
                console.warn("Server page counting failed, using estimate:", data.message);
                resolve(estimatePageCount(file.type, file.size));
            }
        })
        .catch(error => {
            console.warn("Error in PDF page counting service, using estimate:", error);
            resolve(estimatePageCount(file.type, file.size));
        });
    });
}

// Function to estimate page count based on file type and size (fallback method)
function estimatePageCount(fileType, fileSize) {
    // This is a very rough estimation - in a real application, you'd want to extract the actual page count
    if (fileType === 'application/pdf') {
        // Estimate 1 page per 50KB for PDFs
        return Math.max(1, Math.ceil(fileSize / 50000));
    } else if (fileType.includes('word')) {
        // Estimate 1 page per 20KB for Word documents
        return Math.max(1, Math.ceil(fileSize / 20000));
    } else {
        // For images, assume 1 page
        return 1;
    }
}

// Reset the form after submission
function resetForm() {
    // Clear uploaded files
    uploadedFiles = [];
    totalPageCount = 0;
    
    // Clear any displayed files
    if (uploadedArea) {
        uploadedArea.innerHTML = '';
        uploadedArea.style.display = 'none';
    }
    
    if (progressArea) {
        progressArea.innerHTML = '';
        progressArea.style.display = 'none';
    }
    
    // Reset options to defaults
    selectedColor = { value: "bw", label: "Black & White" };
    selectedLayout = { value: "portrait", label: "Portrait" };
    selectedSides = { value: "one-sided", label: "One-sided" };
    selectedPagesOption = { value: "all", label: "All" };
    numberOfCopies = 1;
    
    // Update UI
    document.querySelector('input[name="color"][value="bw"]').checked = true;
    document.querySelector('input[name="layout"][value="portrait"]').checked = true;
    document.querySelector('input[name="sides"][value="one-sided"]').checked = true;
    document.querySelector('input[name="pages"][value="all"]').checked = true;
    if (customPageRangeInput) {
        customPageRangeInput.value = "";
        customPageRangeInput.disabled = true;
    }
    if (copiesInput) copiesInput.value = 1;
    
    // Update summary
    updateSummary();
    
    // Disable print button
    if (printBtn) printBtn.disabled = true;
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