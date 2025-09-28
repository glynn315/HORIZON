<template>
  <div class="flex h-screen w-full flex-wrap bg-white">
    <aside class="relative flex h-screen w-60 flex-col bg-white">
      <h1 class="flex pl-10 pt-14 text-2xl !font-bold font-normal text-zinc-800">&lt;/HORIZON&gt;</h1>
      <nav class="space-y-2 mt-6">
        <div
          v-for="(topic, index) in topics"
          :key="index"
          class="flex justify-between items-center pr-4"
        >
          <button
            class="block w-full text-left px-6 py-3 text-base text-black font-normal hover:bg-gray-100 transition duration-200"
            :class="{ 'font-bold': currentTopicIndex === index }"
            :disabled="!topic.unlocked"
            @click="goToTopic(index)"
          >
            {{ topic.title }}
          </button>
          <span v-if="topic.completed" class="ml-2 text-green-500">✓</span>
        </div>
      </nav>
      <a
        href="dashboard"
        class="absolute bottom-10 left-10 text-base font-normal text-zinc-800 hover:text-indigo-600"
      >
        <button class="mt-6 text-sm text-gray-600 hover:underline">&larr; Back</button>
      </a>
    </aside>
    <main class="flex-1 p-10 overflow-y-scroll h-dvh">
      <div v-if="currentTopic">
        <div v-if="ModuleTopic">
          <h1 class="text-2xl font-bold mb-1 text-black">{{ currentTopic.title }}</h1>
          <p class="text-sm text-gray-800 mb-6">{{ currentTopic.module_name }}</p>

          <div class="h-[700px] overflow-auto scrollbar-w-1">
            <div v-html="currentTopic.content" class="leading-relaxed text-black"></div>
          </div>

          <div v-if="currentTopic.code" class="bg-gray-900 text-green-300 p-4 rounded my-4 font-mono">
            <pre>{{ currentTopic.code }}</pre>
          </div>

          <div class="mt-5 flex justify-end">
            <button
              @click="startPostTest"
              class="py-3 bg-green-600 rounded-full w-72 text-black"
            >
              Start Post Test
            </button>
          </div>
        </div>

        <div v-else-if="postTestDisplay">
          

          <!-- Question Block -->
          <div v-if="PostTest.length && currentPostQuestionIndex < PostTest.length" class="items-center flex justify-center gap-4 mb-4 w-full mt-[200px]">
            <div class="text-black p-10 rounded-xl w-full max-w-2xl mx-auto">
              <h1 class="text-4xl text-black">QUIZ</h1>
              <div class="border border-2 border-black p-7 rounded-3xl mt-3">
                <p class="font-medium text-2xl mb-2">
                  Question {{ currentPostQuestionIndex + 1 }}:<br />
                  {{ PostTest[currentPostQuestionIndex].description }} ?
                </p>
                <div
                  v-if="PostTest[currentPostQuestionIndex].questionType === 'Choices'"
                  class="rounded-2xl"
                >
                  <button
                    v-for="(choice, i) in PostTest[currentPostQuestionIndex].choices"
                    :key="i"
                    class="flex items-center gap-4 w-full p-4 pl-7 mb-3 text-left rounded-full border-2 transition-all duration-200 hover:bg-gray-600"
                    :class="{
                      'border-blue-500 bg-blue-600 text-black': PostTest[currentPostQuestionIndex].userAnswer === choice,
                      'border-gray-400 bg-gray-700 text-gray-200': PostTest[currentPostQuestionIndex].userAnswer !== choice
                    }"
                    @click="selectChoice(choice)"
                  >
                    {{ choice }}
                  </button>
                </div>
                <div
                  v-else-if="PostTest[currentPostQuestionIndex].questionType === 'Blank'"
                  class="flex flex-col justify-end items-end"
                >
                  <input
                    type="text"
                    class="border p-1 rounded w-full text-black"
                    v-model="PostTest[currentPostQuestionIndex].userAnswer"
                    placeholder="Type your answer..."
                    @keydown.enter="onAnswerPost"
                  />
              </div>
              </div>
              <div class="w-full flex justify-end">
                <button @click="onAnswerPost" class="mt-2 bg-[#4AC887] text-black px-4 py-2 rounded-full w-32">
                  Next
                </button>
              </div>
            </div>
          </div>
          <div v-else-if="!PostTest.length && !skillTests.length && !quizCompleted" class="text-center mt-10">
            <p class="text-xl text-gray-300 mb-4">No tests available for this topic.</p>
            <button
              v-if="hasNextTopic"
              @click="goToNextTopic"
              class="bg-blue-600 px-6 py-3 rounded-full text-black text-lg"
            >
              Continue to Next Topic
            </button>
            <button
              v-else
              @click="completeCourse"
              class="bg-green-600 px-6 py-3 rounded-full text-black text-lg"
            >
              🎓 Complete Course
            </button>
          </div>
          <div v-else-if="PostTest.length && currentPostQuestionIndex >= PostTest.length" class=" flex flex-row justify-center w-full">
            <div class="border text-center p-10 w-1/3 rounded-2xl">
              <p class="text-black text-xl mb-4">You completed all Post-Test questions.</p>
              <button
                @click="submitQuiz('post')"
                class="bg-green-600 text-black px-4 py-2 rounded mt-4"
              >
                Submit Post-Test
              </button>
            </div>
          </div>
        </div>
        <div v-else-if="quizCompleted" class="text-center mt-10 text-black">
          <p class="text-2xl mb-2">
            {{ postTestResult === 'pass' ? 'CONGRATULATIONS' : 'Oops! You didnt pass this quiz' }}
          </p>
          <p v-if="maxScore" class="text-xl mb-6">
            Your Score: {{ userTotalScore }} / {{ maxScore }}
          </p>
          <button
            v-if="postTestResult === 'fail'"
            @click="retryLessons"
            class="bg-red-600 px-6 py-3 rounded-full text-black text-lg"
          >
            Retry from First Lesson
          </button>
          <div v-else>
            <div v-if="skillTests.length && !skillTestsCompleted">
              <p class="text-2xl mb-4">Now it’s time for your Skill Test!</p>

              <div
                v-for="(test, i) in skillTests"
                :key="test.id"
                class="bg-gray-900 p-6 rounded-lg text-left mb-8"
              >
                <h2 class="text-xl font-semibold mb-2">{{ test.title }}</h2>
                <p class="mb-4 text-gray-300">{{ test.description }}</p>

                <div v-if="test.codesnippet">
                  <img
                    :src="`data:image/png;base64,${test.codesnippet}`"
                    class="my-4 rounded shadow w-full"
                  />
                </div>

                <textarea
                  v-model="test.userCode"
                  rows="8"
                  class="w-full rounded-md p-3 text-black"
                ></textarea>

                <!-- Run code -->
                <button
                  @click="runCode(test, i)"
                  class="mt-4 bg-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-700"
                >
                  Run Code
                </button>

                <!-- Show result -->
                <div v-if="test.result" class="mt-4 p-3 bg-gray-800 rounded-lg">
                  <h3 class="text-lg font-semibold">Result:</h3>
                  <pre class="text-green-400">{{ test.result.output }}</pre>
                </div>

                <!-- Show submit button only after run -->
                <button
                  v-if="test.showSubmit && !test.submitted"
                  @click="submitSkillTest(test)"
                  class="mt-4 bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700"
                >
                  Submit Answer
                </button>

                <!-- After submission show status -->
                <div v-if="test.submitted" class="mt-2 text-blue-400">
                  ✅ Submitted! Score recorded.
                </div>
              </div>
            </div>

            <div v-else-if="skillTestsCompleted && skillTests.length">
              <button
                v-if="hasNextTopic"
                @click="goToNextTopic"
                class="bg-green-600 px-6 py-3 rounded-full text-black text-lg"
              >
                Continue to Next Topic
              </button>
              <button
                v-else
                @click="completeCourse"
                class="bg-blue-600 px-6 py-3 rounded-full text-black text-lg"
              >
                Complete Course
              </button>
            </div>

            <!-- No Skill Tests Case -->
            <div v-else-if="!skillTests.length">
              <button
                @click="hasNextTopic ? goToNextTopic() : completeCourse()"
                class="bg-green-600 px-6 py-3 rounded-full text-black text-lg"
              >
                Continue
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { usePage } from '@inertiajs/vue3';
export default {
  props: {
    courseId: Number,
    difficulty: Number,
  },
  data() {
    return {
      currentTopicIndex: 0,
      topics: [],
      PostTest: [],
      lessons: [],
      userTotalScore: 0,
      maxScore: 0,
      postTestDisplay: false,
      postTestResult: null,
      quizCompleted: false,
      ModuleTopic: true,
      timer: null,
      startTime: null,
      elapsedTime: 0,
      currentPostQuestionIndex: 0,
      selectedDifficulty: null,
      skillTestAvailable: false,
      skillTests: [],
      skillTestsCompleted: false,
      skillTestTotalScore: 0,
      skillTestMaxScore: 0,
      code: "",
      result: null,
    };
  },
  computed: {
    currentTopic() {
      return this.topics[this.currentTopicIndex];
    },
    hasNextTopic() {
      return this.currentTopicIndex + 1 < this.topics.length;
    },
    isLastTopic() {
      return this.currentTopicIndex === this.topics.length - 1;
    },
    user() {
      return usePage().props.auth.user;
    },
  },
  mounted() {
    this.fetchUserDifficulty().then(() => {
      this.fetchTopics().then(() => {
        this.checkCompletedTopics();
        this.startTimer();
      });
    });
    this.skillTests = this.skillTests.map(t => ({
      ...t,
      attempted: false,
      passed: false
    }));
    window.addEventListener("beforeunload", this.beforeUnloadHandler);
  },

  beforeUnmount() {
    window.removeEventListener("beforeunload", this.beforeUnloadHandler);
  },
  methods: {
    beforeUnloadHandler(event) {
      if (this.postTestDisplay || (!this.skillTestsCompleted && this.skillTests.length > 0)) {
        event.preventDefault();
        event.returnValue = "⚠️ If you leave, your quiz will be canceled and you’ll get zero.";
        return event.returnValue;
      }
    },
    selectChoice(choice) {
      this.PostTest[this.currentPostQuestionIndex].userAnswer = choice;
      this.onAnswerPost();
    },
    async fetchUserDifficulty() {
      const res = await fetch(`/api/user-difficulty/${this.user.id}`);
      const result = await res.json();
      const difficultyRecord = result.find(d => d.course_id === 1);
      this.selectedDifficulty = difficultyRecord?.difficulty_level || 1;
    },
    async fetchSkillTests() {
      try {
        const res = await fetch(`/api/skill-tests/by-topic/${this.currentTopic.id}`);
        if (res.ok) {
          const tests = await res.json();
          this.skillTests = tests.map(t => ({
            ...t,
            userCode: "",
            result: null,
            passed: false,
          }));
        }
      } catch (e) {
        console.error("Skill test fetch failed", e);
      }
    },

    async runCode(test, index) {
      try {
        const res = await fetch("/api/skill-tests/run", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            user_id: this.user.id,
            skill_test_id: test.id,
            code: test.userCode,
            language: test.language,
          }),
        });

        const data = await res.json();
        test.result = data;

        if (data.correct || data.passed) {
          test.passed = true;
          test.result.output = "✅ Passed! Good job.";
        } else {
          test.result.output = "❌ Failed: " + (data.error || "Try again.");
        }
        test.showSubmit = true;
      } catch (err) {
        test.result = { output: "Error running code" };
      }
    },
    async submitSkillTest(test) {
      test.submitted = true;
      test.showSubmit = false;

      this.calculateSkillTestScore();
      const allDone = this.skillTests.every(t => t.submitted);
      if (allDone) {
        this.skillTestsCompleted = true;
      }
    },



    calculateSkillTestScore() {
      this.skillTestMaxScore = this.skillTests.reduce((acc, t) => acc + (t.score || 1), 0);
      this.skillTestTotalScore = this.skillTests.reduce((acc, t) => acc + (t.passed ? (t.score || 1) : 0), 0);

      if (this.skillTests.every(t => t.attempted)) {
        this.skillTestsCompleted = true;
        this.unlockTopicsBasedOnProgress();
      }
    },
    async fetchLessons() {
      const topicId = this.currentTopic?.id;
      if (!topicId) return;

      const res = await fetch(`/api/topics/${topicId}/lessons`);
      const allLessons = await res.json();

      this.lessons = allLessons.filter(lesson => lesson.difficulty === this.selectedDifficulty);
    },
    async fetchTopics() {
      const res = await fetch(`/api/courses/1/topics`);
      const topics = await res.json();

      const allowedDifficulties = [1];
      if (this.selectedDifficulty >= 2) allowedDifficulties.push(2);
      if (this.selectedDifficulty === 3) allowedDifficulties.push(3);

      let filteredTopics = topics.filter(topic => allowedDifficulties.includes(topic.difficulty));
      this.topics = filteredTopics.map(topic => ({ ...topic, unlocked: false }));

      await this.unlockTopicsBasedOnProgress();
    },
    async startSkillTest(test) {
      if (!test) return;

      try {
        const res = await fetch(`/api/skill-tests/run`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            user_id: this.user.id,
            skill_test_id: test.id,
            code: "// user code here or open editor UI"
          }),
        });

        const result = await res.json();

        if (result.passed) {
          alert("🎉 Skill Test Passed!");
          test.passed = true;
        } else {
          alert("❌ Skill Test Failed: " + (result.error || "Try again."));
          test.passed = false;
        }

        test.attempted = true;
        if (this.skillTests.every(t => t.attempted)) {
          this.skillTestsCompleted = true;
        }
      } catch (err) {
        console.error("Skill test failed", err);
      }
    },

    goToTopic(index) {
      if (!this.topics[index].unlocked) return;
      this.currentTopicIndex = index;
      this.ModuleTopic = true;
      this.postTestDisplay = false;
      this.quizCompleted = false;
      this.postTestResult = null;
      this.currentPostQuestionIndex = 0;
      this.stopTimer();
      this.resetTimer();
      this.fetchLessons();
      this.startTimer();
    },
    async fetchPostTest() {
      const topicId = this.currentTopic?.id;
      if (!topicId) return;
      try {
        const res = await fetch(`/api/displayPostQuiz/${topicId}`);
        const questions = await res.json();
        const enriched = await Promise.all(
          questions.map(async q => {
            if (q.questionType === 'Choices') {
              try {
                const optRes = await fetch(`/api/options/by-question/${q.id}`);
                const options = await optRes.json();
                const shuffled = options.map(o => o.option_text).sort(() => Math.random() - 0.5);
                q.choices = shuffled;
              } catch (err) {
                console.error(`Error fetching options for question ${q.id}:`, err);
                q.choices = [];
              }
            }
            return { ...q, userAnswer: '', isCorrect: false };
          })
        );
        this.PostTest = enriched;
      } catch (error) {
        console.error('Error loading post-test:', error);
        this.PostTest = [];
      }
    },
    async checkCompletedTopics() {
      try {
        const res = await fetch(`/api/user-topic-progress/${this.user.id}/1`);
        const completedTopics = await res.json();
        const completedIds = completedTopics.map(t => t.topic_id);

        this.topics = this.topics.map((topic, index) => {
          topic.unlocked = index === 0 || completedIds.includes(topic.id);
          topic.completed = completedIds.includes(topic.id);
          return topic;
        });
      } catch (error) {
        console.error("Failed to fetch completed topics", error);
      }
    },
    async unlockTopicsBasedOnProgress() {
      try {
        const res = await fetch(`/api/user-topic-progress/${this.user.id}/1`);
        const completed = await res.json();
        const completedIds = completed.map(item => item.topic_id);

        this.topics = this.topics.map((topic, index, arr) => {
          const isFirst = index === 0;
          const isPassed = completedIds.includes(topic.id);
          const prevPassed = index > 0 && completedIds.includes(arr[index - 1].id);

          topic.unlocked = isFirst || isPassed || prevPassed;
          return topic;
        });
      } catch (error) {
        console.error('Failed to unlock topics:', error);
      }
    },
    onAnswerPost() {
      const question = this.PostTest[this.currentPostQuestionIndex];
      if (!question.userAnswer) return;
      question.isCorrect =
        question.questionType === 'Blank'
          ? question.userAnswer.trim().toLowerCase() === question.answer.trim().toLowerCase()
          : question.userAnswer === question.answer;

      setTimeout(() => {
        this.currentPostQuestionIndex++;
      }, 300);
    },
    async submitQuiz(type) {
      this.stopTimer();
      const testData = this.PostTest;
      const totalScore = testData.reduce((acc, q) => acc + (q.score || 1), 0);
      const userScore = testData.reduce((acc, q) => acc + (q.isCorrect ? (q.score || 1) : 0), 0);
      const passed = userScore >= Math.ceil(totalScore * 0.6);
      this.userTotalScore = userScore;
      this.maxScore = totalScore;
      const attempts = testData.map(q => ({
        user_id: this.user.id,
        topic_id: this.currentTopic.id,
        quiz_id: q.id,
        type,
        score: q.isCorrect ? (q.score || 1) : 0,
        total: q.score || 1,
        passed: q.isCorrect,
        difficulty: q.difficulty || this.selectedDifficulty,
        time_taken: this.elapsedTime,
        timestamp: new Date().toISOString(),
      }));
      for (const attempt of attempts) {
        await fetch('/api/recordAttempt', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(attempt),
        });
      }
      this.postTestDisplay = false;
      this.quizCompleted = true;
      this.postTestResult = passed ? 'pass' : 'fail';
      if (passed) {
        await fetch('/api/topics/complete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            user_id: this.user.id,
            topic_id: this.currentTopic.id,
          }),
        });
        try {
          await this.fetchSkillTests();
        } catch (e) {
          console.error("Skill test fetch failed", e);
        }
      }
    },
    async startPostTest() {
      this.ModuleTopic = false;
      this.stopTimer();
      this.resetTimer();
      const topicId = this.currentTopic?.id;
      if (!topicId) return;
      await this.fetchPostTest();
      if (!this.PostTest.length) {
        await this.fetchSkillTests();

        if (!this.skillTests.length) {
          await fetch('/api/topics/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              user_id: this.user.id,
              topic_id: this.currentTopic.id,
            }),
          });

          if (this.hasNextTopic) {
            this.goToNextTopic();
          } else {
            this.skillTestsCompleted = true;
          }
        } else {
          this.quizCompleted = true;
          this.postTestResult = "pass"; 
        }
      } else {
        this.postTestDisplay = true;
        this.startTimer();
      }
    },
    async goToNextTopic() {
      const nextIndex = this.currentTopicIndex + 1;
      if (nextIndex >= this.topics.length) return;
      this.topics[nextIndex].unlocked = true;
      this.topics[this.currentTopicIndex].completed = true;
      this.goToTopic(nextIndex);
    },

    retryLessons() {
      this.ModuleTopic = true;
      this.postTestDisplay = false;
      this.quizCompleted = false;
      this.postTestResult = null;
      this.fetchLessons();
    },
    startTimer() {
      this.stopTimer();
      this.startTime = Date.now();
      this.elapsedTime = 0;

      this.timer = setInterval(() => {
        this.elapsedTime = Math.floor((Date.now() - this.startTime) / 1000);
      }, 1000);
    },
    stopTimer() {
      clearInterval(this.timer);
      this.timer = null;
    },
    resetTimer() {
      this.elapsedTime = 0;
      this.startTime = null;
    },
    async completeCourse() {
      try {
        await fetch('/api/courses/complete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            user_id: this.user.id,
            course_id: this.courseId,
          }),
        });
        window.location.href = "/dashboard";
      } catch (error) {
        console.error("Failed to complete course:", error);
      }
    },
  },
};
</script>
