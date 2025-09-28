<template>
  <div class="flex h-screen w-full flex-wrap bg-zinc-800">
      <aside class="flex h-screen w-60 flex-col bg-white">
        <h1 class="p-10 text-2xl font-normal text-zinc-800 w-full text-center !font-bold">{{ header }}</h1>
        <div class="flex flex-row items-center gap-3 px-3 py-0">
          <div class=" border h-16 w-16 rounded-full overflow-hidden">
            <img :src="user.photo" alt="" class="w-full h-full">
          </div>
          <p v-if="user" class="text-base font-normal text-zinc-800 p-2">
          {{ user.firstname }} {{ user.lastname }}
          </p>
        </div>

        <nav class="flex flex-col space-y-6 pl-10 pt-14">
          <a href="dashboard" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <LayoutDashboard />
            Dashboard
          </a>
          <a href="/test" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <BookCopyIcon />Course</a> 
          <a href="/mylearning" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <BookCheckIcon/>My Learning</a> 
          <a href="/sandpit" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <TerminalSquareIcon/>Sandpit</a> 
          <a href="/badges" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <BadgeCheckIcon/>Badge</a> 

          <a href="/settings" class="text-base font-medium text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
            <UserCircle/>Profiles</a>
        </nav>

        <!-- Logout -->
        <nav class="flex flex-col space-y-6 pl-20 pt-60">
          <a
            href="#"
            @click.prevent="showLogoutModal = true"
            class="text-base font-normal text-zinc-800 hover:text-indigo-600"
          >
            Logout
          </a>
        </nav>
      </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10">
      <h2 class="text-3xl font-bold text-white mb-8">Keep Learning</h2>

      <div v-for="course in ['Vue', 'Laravel']" :key="course" class="relative mb-20">
        <div class="h-60 w-[400px] rounded-[30px] bg-white"></div>
        <h3 class="absolute left-[50px] top-[40px] text-xl font-normal text-stone-900">Course</h3>
        <h4 class="absolute left-[50px] top-[70px] text-2xl font-semibold text-stone-900">{{ course }}</h4>
        <p class="absolute left-[50px] top-[120px] w-80 text-xs font-normal text-stone-900">
          {{ course === 'Vue'
            ? 'An open-source MVVM front-end framework for building user interfaces.'
            : 'A PHP-based web framework for building web applications.' }}
        </p>
        <p v-if="difficultyMap[course]" class="absolute left-[50px] top-[180px] text-sm text-green-700">
          Difficulty: {{ ['Beginner', 'Intermediate', 'Advanced'][difficultyMap[course] - 1] }}
        </p>
        <button
          class="absolute left-[270px] top-[175px] text-xl font-medium text-stone-900 hover:text-green-500"
          @click.prevent="startCourse(course)"
        >Start</button>

        <!-- Progress Circle -->
        <div class="absolute left-[500px] top-[30px] flex flex-col items-center">
          <div
            class="relative w-[160px] h-[160px] rounded-full"
            :style="{
              background: `conic-gradient(#288feb ${courseProgress[course]}%, #e5e7eb ${courseProgress[course]}% 100%)`
            }"
          >
            <div class="absolute top-1/2 left-1/2 w-[130px] h-[130px] bg-zinc-800 rounded-full transform -translate-x-1/2 -translate-y-1/2"></div>
          </div>
          <p class="text-white mt-2">{{ course }} Progress {{ courseProgress[course] }}%</p>
        </div>
      </div>
    </main>

    <!-- Pretest Modal -->
    <div v-if="showPretestModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="w-full max-w-3xl rounded-lg bg-white p-6 text-gray-800 shadow-lg">
        <h2 class="mb-4 text-xl font-semibold text-center">{{ selectedCourse }} Pretest</h2>
        <div class="text-center mb-4 text-red-600 font-bold text-lg">
          Time Remaining: {{ Math.floor(timeLeft / 60) }}:{{ (timeLeft % 60).toString().padStart(2, '0') }}
        </div>

        <div v-if="pretestQuestions.length && currentQuestionIndex < pretestQuestions.length">
          <p class="text-lg font-semibold mb-4">Question {{ currentQuestionIndex + 1 }}:</p>
          <p class="mb-4">{{ pretestQuestions[currentQuestionIndex].description }}</p>

          <div v-if="pretestQuestions[currentQuestionIndex].questionType === 'Choices'">
            <label v-for="(choice, i) in pretestQuestions[currentQuestionIndex].choices" :key="i" class="block mb-2">
              <input type="radio" :value="choice" v-model="pretestQuestions[currentQuestionIndex].userAnswer" class="mr-2" />
              {{ choice }}
            </label>
          </div>

          <div v-else-if="pretestQuestions[currentQuestionIndex].questionType === 'Blank'">
            <input type="text" v-model="pretestQuestions[currentQuestionIndex].userAnswer" class="w-full rounded border p-2" placeholder="Type your answer..." />
          </div>

          <div class="mt-4 flex justify-end">
            <button @click="nextPretestQuestion" class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">Next</button>
          </div>
        </div>

        <div v-else>
          <p class="text-center text-lg mb-4">You've completed the pretest.</p>
          <div class="flex justify-center">
            <button @click="submitPretest" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Submit</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Logout Modal -->
    <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center text-gray-800 shadow-lg">
        <h2 class="mb-4 text-xl font-semibold">Are you sure?</h2>
        <p class="mb-6">Do you really want to logout from your account?</p>
        <div class="flex justify-center space-x-4">
          <button @click="confirmLogout" class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600">Yes, Logout</button>
          <button @click="showLogoutModal = false" class="rounded bg-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-400">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { LayoutDashboard , BookCopyIcon, BookCheckIcon, TerminalSquareIcon, BadgeCheckIcon, UserCircle } from 'lucide-vue-next';

const header = "</HORIZON>"
const { auth } = usePage().props;
const user = auth?.user;
const timeLeft = ref(0);
let timerInterval = null;
const showLogoutModal = ref(false);
const showPretestModal = ref(false);
const selectedCourse = ref('');
const pretestQuestions = ref([]);
const currentQuestionIndex = ref(0);
const difficultyMap = ref({});
const courseProgress = ref({ Vue: 0, Laravel: 0 });

function confirmLogout() {
  router.post('/logout', {}, {
    onFinish: () => {
      window.location.href = '/logout';
    }
  });
}

function startCourse(course) {
  if (!difficultyMap.value[course]) {
    selectedCourse.value = course;
    fetchPretestQuestions(course);
  } else {
    router.visit(`/module${course.toLowerCase()}`);
  }
}

function retestPretest(course) {
  selectedCourse.value = course;
  fetchPretestQuestions(course);
}

async function fetchPretestQuestions(course) {
  try {
    const res = await fetch(`/api/pretest/${course}`);
    const data = await res.json();

    pretestQuestions.value = await Promise.all(
      data.map(async (q) => {
        let choices = [];
        if (q.questionType === 'Choices') {
          const optRes = await fetch(`/api/options/by-question/${q.id}`);
          const optData = await optRes.json();
          choices = optData.map(opt => opt.option_text).sort(() => Math.random() - 0.5);
        }
        return { ...q, choices, userAnswer: '' };
      })
    );

    currentQuestionIndex.value = 0;
    showPretestModal.value = true;

    timeLeft.value = pretestQuestions.value.length * 60;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      if (timeLeft.value > 0) {
        timeLeft.value--;
      } else {
        clearInterval(timerInterval);
        submitPretest(); // auto submit on timeout
      }
    }, 1000);
  } catch (error) {
    console.error('Failed to load pretest questions:', error);
  }
}

function nextPretestQuestion() {
  if (pretestQuestions.value[currentQuestionIndex.value].userAnswer !== '') {
    currentQuestionIndex.value++;
  }
}

async function submitPretest() {

  if (timerInterval) clearInterval(timerInterval);
  const answered = pretestQuestions.value;
  const correctCount = answered.filter(q =>
    q.userAnswer?.toLowerCase().trim() === q.answer?.toLowerCase().trim()
  ).length;

  const total = answered.length;
  const percentage = correctCount / total;

  let difficulty_level = 1;
  if (percentage >= 1) difficulty_level = 3;
  else if (percentage >= 0.5) difficulty_level = 2;

  try {
    await fetch('/api/user-difficulty', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: user.id,
        course_name: selectedCourse.value,
        difficulty_level,
        score: correctCount
      })
    });

    difficultyMap.value[selectedCourse.value] = difficulty_level;
    showPretestModal.value = false;
    router.visit(`/module${selectedCourse.value.toLowerCase()}`);
  } catch (err) {
    console.error(err);
    alert('Failed to save difficulty.');
  }
}

onMounted(async () => {
  try {
    const [progressRes, diffRes] = await Promise.all([
      fetch(`/api/user-progress/${user.id}`),
      fetch(`/api/user-difficulty/${user.id}`)
    ]);

    const progressData = await progressRes.json();
    const difficultyData = await diffRes.json();

    courseProgress.value.Vue = progressData.progress_by_course.find(c => c.course_name === 'Vue')?.overall_progress || 0;
    courseProgress.value.Laravel = progressData.progress_by_course.find(c => c.course_name === 'Laravel')?.overall_progress || 0;

    difficultyData.forEach(entry => {
      difficultyMap.value[entry.course_name] = entry.difficulty_level;
    });
  } catch (error) {
    console.error('Error fetching progress or difficulty:', error);
  }
});
</script>
