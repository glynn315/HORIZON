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

    <main class="flex-1 p-12">
      <!-- All Badges -->
      <h2 class="text-2xl font-bold mb-4 text-white border-b-2 pb-2">All Badges</h2>
      <div class="rounded shadow">
        <div v-if="badges.length">
          <ul class="grid grid-cols-4 gap-4">
            <li v-for="badge in badges" :key="badge.id" class="p-3 flex items-center gap-4 flex-col">
              <div class="text-center w-full flex flex-col justify-center items-center">
                <img :src="badge.image" alt="Badge Image" class="w-20 h-20 object-cover rounded-full" />
                <h4 class="font-semibold text-white">{{ badge.title }}</h4>
                <p class="text-sm text-gray-50">{{ badge.description }}</p>
              </div>
            </li>
          </ul>
        </div>
        <div v-else>
          <p>No badges available at the moment.</p>
        </div>
      </div>

      <!-- Topic Badges -->
      <h2 class="text-2xl font-bold mt-10 mb-4 text-white border-b-2 pb-2">Topic Badges</h2>
      <div class="rounded shadow">
        <div v-if="topicBadges.length">
          <ul class="grid grid-cols-4 gap-4">
            <li v-for="badge in topicBadges" :key="badge.id" class="p-3 flex items-center gap-4 flex-col">
              <div class="text-center w-full flex flex-col justify-center items-center">
                <img :src="badge.image" alt="Badge Image" class="w-20 h-20 object-cover rounded-full" />
                <h4 class="font-semibold text-white">{{ badge.title }}</h4>
                <p class="text-sm text-gray-50">{{ badge.description }}</p>
                <span class="text-green-400 text-sm mt-2">🏆 Earned for completing: {{ badge.topic_title }}</span>
              </div>
            </li>
          </ul>
        </div>
        <div v-else>
          <p class="text-gray-400">No topic badges yet.</p>
        </div>
      </div>
    </main>
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
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import { LayoutDashboard , BookCopyIcon, BookCheckIcon, TerminalSquareIcon, BadgeCheckIcon, UserCircle } from 'lucide-vue-next';
const { auth } = usePage().props
const user = auth.user
const header = "</HORIZON>"
const badges = ref([])
const topicBadges = ref([])
const showLogoutModal = ref(false)

const fetchBadges = async () => {
  try {
    const [allBadges, claimedBadges, topicBadgesRes] = await Promise.all([
      axios.get(`/api/validateBadge/${user.id}`),
      axios.get(`/api/badges/claimed/${user.id}`),
      axios.get(`/api/topic-badges/${user.id}`)
    ])

    const claimedIds = claimedBadges.data.map(b => b.id)

    badges.value = allBadges.data.badges.map(badge => ({
      ...badge,
      claimed: claimedIds.includes(badge.id),
      progress: allBadges.data.progressData[badge.id]?.progress || 0,
      claimable: allBadges.data.progressData[badge.id]?.claimable || false
    }))

    topicBadges.value = topicBadgesRes.data.badges
  } catch (error) {
    console.error('Failed to fetch badges:', error)
  }
}

const claimBadge = async (badgeId) => {
  try {
    const res = await axios.post(`/api/badges/claim/${user.id}`, {
      badge_id: badgeId
    })
    alert(res.data.message)
    await fetchBadges()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to claim badge.')
  }
}

const confirmLogout = () => {
  router.post('/logout')
}

onMounted(fetchBadges)
</script>

