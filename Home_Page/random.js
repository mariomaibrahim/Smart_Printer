  // DOM elements
  const page1 = document.getElementById('page1');
  const page2 = document.getElementById('page2');
  const randomNumberDisplay = document.getElementById('random-number');
  const generateBtn = document.getElementById('generate-btn');
  const printBtn = document.getElementById('print-btn');
  const copySuccess = document.getElementById('copy-success');
  const digitInputs = document.querySelectorAll('.digit-input');
  const doneBtn = document.getElementById('done-btn');
  const backBtn = document.getElementById('back-btn');
  const matchFeedback = document.getElementById('match-feedback');

  // Current random number
  let currentRandomNumber = '';

  // Function to generate random 6-digit number
  function generateRandomNumber() {
      const randomNumber = Math.floor(100000 + Math.random() * 900000).toString();
      currentRandomNumber = randomNumber;

      // Add animation effect
      randomNumberDisplay.classList.remove('animate-number');
      void randomNumberDisplay.offsetWidth; // Trigger reflow
      randomNumberDisplay.classList.add('animate-number');

      randomNumberDisplay.textContent = randomNumber;

      // Set number in local storage
      localStorage.setItem('randomNumber', randomNumber);

      return randomNumber;
  }

  // Function to copy number to clipboard
  function copyNumber() {
      const number = randomNumberDisplay.textContent;
      navigator.clipboard.writeText(number).then(() => {
          // Show copy success notification
          copySuccess.style.opacity = '1';
          setTimeout(() => {
              copySuccess.style.opacity = '0';
          }, 2000);
      });
  }

  // Function to switch pages
  function showPage(pageId) {
      if (pageId === 'page1') {
          page1.classList.add('active');
          page2.classList.remove('active');
      } else {
          page1.classList.remove('active');
          page2.classList.add('active');

          // Set focus on first input
          digitInputs[0].focus();

          // Pre-fill inputs if there's a saved number
          const savedNumber = localStorage.getItem('randomNumber');
          if (savedNumber && savedNumber.length === 6) {
              for (let i = 0; i < 6; i++) {
                  digitInputs[i].value = savedNumber.charAt(i);
              }
          }
      }
  }

  // Function to handle input navigation
  function setupInputNavigation() {
      digitInputs.forEach((input, index) => {
          // Move to next input when a digit is entered
          input.addEventListener('input', (e) => {
              if (e.target.value !== '') {
                  // Move to next input
                  if (index < digitInputs.length - 1) {
                      digitInputs[index + 1].focus();
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
      const savedNumber = localStorage.getItem('randomNumber');
      let enteredNumber = '';

      digitInputs.forEach(input => {
          enteredNumber += input.value;
      });

      if (enteredNumber === savedNumber) {
          matchFeedback.style.display = 'block';
          matchFeedback.textContent = 'الرقم المدخل مطابق!';
          matchFeedback.style.color = '#4CAF50';
      } else {
          matchFeedback.style.display = 'block';
          matchFeedback.textContent = 'الرقم غير مطابق!';
          matchFeedback.style.color = '#f44336';
      }

      setTimeout(() => {
          matchFeedback.style.display = 'none';
      }, 3000);
  }

  // Initialize app
  function init() {
      // Generate a random number on page load
      generateRandomNumber();

      // Set up event listeners
      generateBtn.addEventListener('click', generateRandomNumber);
      printBtn.addEventListener('click', () => showPage('page2'));
      backBtn.addEventListener('click', () => showPage('page1'));
      doneBtn.addEventListener('click', checkMatchingCode);

      // Set up input navigation
      setupInputNavigation();

      // Make copy icon available globally
      window.copyNumber = copyNumber;
  }

  // Run initialization when DOM is loaded
  document.addEventListener('DOMContentLoaded', init);