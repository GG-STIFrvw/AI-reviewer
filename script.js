let questions = [
    {
      question: "Upload a file to start generating questions.",
      choices: { a: "—", b: "—", c: "—", d: "—" },
      answer: "a"
    }
  ];
  
  let currentQuestionIndex = 0;
  let score = 0;
  
  const questionElement = document.getElementById("question");
  const answerButtons = document.getElementById("answer-buttons");
  const nextButton = document.getElementById("next-btn");
  
  function startQuiz() {
    currentQuestionIndex = 0;
    score = 0;
    nextButton.innerHTML = "Next";
    showQuestion();
  }
  
  function showQuestion() {
    resetState();
    let currentQuestion = questions[currentQuestionIndex];
    questionElement.innerHTML = currentQuestion.question;
  
    for (let key in currentQuestion.choices) {
      const button = document.createElement("button");
      button.innerHTML = `${key.toUpperCase()}: ${currentQuestion.choices[key]}`;
      button.classList.add("btn");
      if (key === currentQuestion.answer) {
        button.dataset.correct = true;
      }
      button.addEventListener("click", selectAnswer);
      answerButtons.appendChild(button);
    }
  }
  
  function resetState() {
    nextButton.style.display = "none";
    while (answerButtons.firstChild) {
      answerButtons.removeChild(answerButtons.firstChild);
    }
  }
  
  function selectAnswer(e) {
    const selectedBtn = e.target;
    const isCorrect = selectedBtn.dataset.correct === "true";
    if (isCorrect) {
      selectedBtn.classList.add("correct");
      score++;
    } else {
      selectedBtn.classList.add("wrong");
    }
  
    Array.from(answerButtons.children).forEach(button => {
      if (button.dataset.correct === "true") {
        button.classList.add("correct");
      }
      button.disabled = true;
    });
  
    nextButton.style.display = "block";
  }
  
  function showScore() {
    resetState();
    questionElement.innerHTML = `You scored ${score} out of ${questions.length}!`;
    nextButton.innerHTML = "Play Again";
    nextButton.style.display = "block";
  }
  
  function handleNextButton() {
    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
      showQuestion();
    } else {
      showScore();
    }
  }
  
  nextButton.addEventListener("click", () => {
    if (currentQuestionIndex < questions.length) {
      handleNextButton();
    } else {
      startQuiz();
    }
  });
  
  const uploadBtn = document.getElementById("uploadBtn");
  const timerElement = document.getElementById("timer");

  function uploadFile() {
    const fileInput = document.getElementById("uploadFile");
    if (!fileInput.files.length) {
      alert("Please select a file.");
      return;
    }

    uploadBtn.disabled = true;
    let timeLeft = 30;
    timerElement.textContent = `Thinking of how to make this hard for you: ${timeLeft}s`;

    const timer = setInterval(() => {
      timeLeft--;
      timerElement.textContent = `Thinking of how to make this hard for you: ${timeLeft}s`;
      if (timeLeft <= 0) {
        clearInterval(timer);
      }
    }, 1000);

    const formData = new FormData();
    formData.append("file", fileInput.files[0]);

    fetch("generate-questions-gemini.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        clearInterval(timer);
        timerElement.textContent = "";
        uploadBtn.disabled = false;
        if (data.error) {
          alert("Error: " + data.error);
          return;
        }
        questions = data; // Replace old questions
        startQuiz();
      })
      .catch(err => {
        clearInterval(timer);
        timerElement.textContent = "";
        uploadBtn.disabled = false;
        console.error(err);
        alert("Something went wrong.");
      });
  }
  
  startQuiz();
  