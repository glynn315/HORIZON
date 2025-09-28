<template>
  <div class="flex h-screen w-full flex-wrap bg-zinc-800">
    <!-- Sidebar -->
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

    <!-- Main Content Area -->
    <main class="flex-1 p-12">
      <div class="w-full rounded flex gap-3">
        <div class="w-2/3 rounded-md">
          <div class="bg-white p-5 rounded-md shadow mb-6">
            <h2 class="text-xl font-semibold text-zinc-800 mb-4">Latest Score per Topic</h2>
            <canvas ref="chartRefScore" height="100"></canvas>
          </div>

          <div class="bg-white p-5 rounded-md shadow mb-6">
            <h2 class="text-xl font-semibold text-zinc-800 mb-4">Total Time per Topic</h2>
            <canvas ref="chartRefTime" height="100"></canvas>
          </div>

        </div>
        <div class="w-1/3 flex flex-col">
          <div class=" rounded-md text-white p-5 border max-h-[450px] min-h-[450px]">
            <h1 class="text-2xl border-b-2 pb-3 mb-3">Leaderboards</h1>
            <div v-if="leaderboard?.length">
              <div
                v-for="(entry, index) in leaderboard"
                :key="entry.id"
                class="bg-white text-zinc-800 mb-3 px-4 py-2 rounded-2xl shadow"
              >
                <div class="flex justify-between items-center">
                  <h2 class="text-lg font-semibold">
                    {{ entry.name }}
                  </h2>
                  <span class="text-sm text-gray-500">#{{ index + 1 }}</span>
                </div>
                <p class="text-sm mt-1">Topics Completed: <strong>{{ entry.topics_completed }}</strong></p>
              </div>
            </div>
            <div v-else class="text-sm text-gray-300 mt-4">No data yet.</div>
          </div>
          <div class="border mt-6 p-5 rounded-md text-white">
            <h2 class="text-2xl border-b-2 pb-3 mb-3">Your Course Progress</h2>

            <div v-if="progressData?.length">
              <div
                v-for="(course, idx) in progressData"
                :key="idx"
                class="mb-4 bg-white text-zinc-800 p-4 rounded shadow"
              >
                <h3 class="text-lg font-semibold">{{ course.course_name }}</h3>
                <p class="text-sm">
                  Topics Completed: <strong>{{ course.topics_completed }}</strong> /
                  {{ course.topics_total }}
                </p>
                <p class="text-sm">Progress: <strong>{{ course.overall_progress }}%</strong></p>
              </div>
            </div>

            <div v-else class="text-sm text-gray-300 mt-4">No course progress yet.</div>
          </div>

        </div>
      </div>
    </main>
  </div>

  <div
    v-if="showLogoutModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center text-gray-800 shadow-lg">
      <h2 class="mb-4 text-xl font-semibold">Are you sure?</h2>
      <p class="mb-6">Do you really want to logout from your account?</p>
      <div class="flex justify-center space-x-4">
        <button
          @click="confirmLogout"
          class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600"
        >
          Yes, Logout
        </button>
        <button
          @click="showLogoutModal = false"
          class="rounded bg-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-400"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'
import { LayoutDashboard , BookCopyIcon, BookCheckIcon, TerminalSquareIcon, BadgeCheckIcon, UserCircle } from 'lucide-vue-next';
Chart.register(...registerables)

const { auth } = usePage().props
const user = auth.user

const chartRefScore = ref(null)
const chartRefTime = ref(null)
const header = "</HORIZON>"
let chartScore = null
let chartTime = null

const leaderboard = ref([])
const progressData = ref([])
const showLogoutModal = ref(false)

onMounted(async () => {
  const lbRes = await fetch('/api/leaderboard')
  leaderboard.value = await lbRes.json()
  const progRes = await fetch(`/api/rating/${user.id}`)
  const progressJson = await progRes.json()
  progressData.value = progressJson.progress_by_course

  // Prepare chart data
  const topicLabels = []
  const scores = []
  const times = []

  progressData.value.forEach(course => {
    course.topics.forEach(topic => {
      topicLabels.push(topic.title)
      scores.push(topic.score)
      times.push(topic.time_taken)
    })
  })

  // Destroy if already exists
  if (chartScore) chartScore.destroy()
  if (chartTime) chartTime.destroy()

  // Chart: Score
  chartScore = new Chart(chartRefScore.value, {
    type: 'line',
    data: {
      labels: topicLabels,
      datasets: [{
        label: 'Score per Topic',
        data: scores,
        borderColor: '#288feb',
        backgroundColor: '#288feb',
        tension: 0.3,
        fill: false,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }},
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Score' }
        }
      }
    }
  })

  // Chart: Time
  chartTime = new Chart(chartRefTime.value, {
    type: 'line',
    data: {
      labels: topicLabels,
      datasets: [{
        label: 'Total Time Spent per Topic (mins)',
        data: times,
        borderColor: '#f97316',
        backgroundColor: '#f97316',
        tension: 0.3,
        fill: false,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }},
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Time (mins)' }
        }
      }
    }
  })
})

const confirmLogout = () => {
  router.post('/logout')
}
</script>
