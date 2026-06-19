<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
    getHealth,
    getOrganization,
    getOrganizationById,
    getOrganizations,
    getOrganizationParseAttempts,
    getOrganizationReviews,
    getParseAttempts,
    getReviews,
    logout,
    saveOrganizationUrl,
} from '../services/api'

const organization = ref(null)
const organizations = ref([])
const selectedOrganizationId = ref(null)
const reviews = ref([])
const attempts = ref([])
const health = ref(null)

const url = ref('https://yandex.kz/maps/org/demo_company/123456789')
const currentPage = ref(1)
const meta = ref(null)

const isLoading = ref(true)
const isParsing = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const router = useRouter()

async function submitLogout() {
    try {
        await logout()
    } finally {
        await router.push('/login')
    }
}

function statusLabel(status) {
    const labels = {
        success: 'Успешно',
        failed: 'Ошибка',
        processing: 'В процессе',
        pending: 'Ожидает',
        partial: 'Частично',
    }

    return labels[status] || status || 'Нет данных'
}

function formatDate(value) {
    if (!value) return '—'

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))
}

async function loadDashboard(page = 1) {
    errorMessage.value = ''
    isLoading.value = true

    try {
        const [
            healthResponse,
            organizationsResponse,
            organizationResponse,
            reviewsResponse,
            attemptsResponse,
        ] = await Promise.all([
            getHealth(),
            getOrganizations(),
            getOrganization(),
            getReviews(page),
            getParseAttempts(),
        ])

        health.value = healthResponse
        organization.value = organizationResponse.data
        organizations.value = organizationsResponse.data
        selectedOrganizationId.value = organizationResponse.data?.id ?? null
        reviews.value = reviewsResponse.data
        meta.value = reviewsResponse.meta
        attempts.value = attemptsResponse.data
        currentPage.value = page
    } catch (error) {
        errorMessage.value = error.message || 'Не удалось загрузить данные'
    } finally {
        isLoading.value = false
    }
}

async function submitOrganization() {
    errorMessage.value = ''
    successMessage.value = ''
    isParsing.value = true

    try {
        const response = await saveOrganizationUrl(url.value)
        organization.value = response.data
        selectedOrganizationId.value = response.data.id
        successMessage.value = 'Организация успешно обновлена'

        await loadDashboard(1)
    } catch (error) {
        errorMessage.value = error.message || 'Не удалось сохранить ссылку'
    } finally {
        isParsing.value = false
    }
}
async function selectOrganization(id, page = 1) {
    errorMessage.value = ''
    isLoading.value = true
    selectedOrganizationId.value = id

    try {
        const [organizationResponse, reviewsResponse, attemptsResponse] = await Promise.all([
            getOrganizationById(id),
            getOrganizationReviews(id, page),
            getOrganizationParseAttempts(id),
        ])

        organization.value = organizationResponse.data
        reviews.value = reviewsResponse.data
        meta.value = reviewsResponse.meta
        attempts.value = attemptsResponse.data
        currentPage.value = page
    } catch (error) {
        errorMessage.value = error.message || 'Не удалось загрузить организацию'
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    loadDashboard()
})
</script>

<template>
    <main class="dashboard-page">
        <header class="dashboard-header">
            <div>
                <span class="dashboard-kicker">Yandex Reviews Parser</span>
                <h1>Панель управления отзывами</h1>
                <p>
                    Сохраняйте ссылку на организацию, запускайте получение данных
                    и смотрите отзывы без перезагрузки страницы.
                </p>
            </div>

            <div class="dashboard-actions">
                <div class="health-card">
                    <span class="health-dot"></span>
                    <div>
                        <strong>API {{ health?.status || 'checking' }}</strong>
                        <small>DB: {{ health?.database || 'checking' }}</small>
                    </div>
                </div>

                <button class="logout-button" type="button" @click="submitLogout">
                    Выйти
                </button>
            </div>
        </header>

        <section v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
        </section>

        <section v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </section>

        <section class="settings-card">
            <div>
                <h2>Ссылка на организацию</h2>
                <p>Вставьте ссылку на карточку организации в Яндекс.Картах.</p>
            </div>

            <form class="url-form" @submit.prevent="submitOrganization">
                <input v-model="url" type="url" placeholder="https://yandex.kz/maps/org/...">

                <button type="submit" :disabled="isParsing">
                    {{ isParsing ? 'Загружаем...' : 'Получить данные' }}
                </button>
            </form>
        </section>

        <section v-if="isLoading" class="grid-layout">
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
            <div class="skeleton-card wide"></div>
        </section>

        <template v-else>
            <section class="panel organization-history-panel">
                <div class="panel-header">
                    <h2>История организаций</h2>
                    <span>{{ organizations.length }}</span>
                </div>

                <div v-if="organizations.length === 0" class="empty-state">
                    Организации пока отсутствуют.
                </div>

                <div v-else class="organization-history-list">
                    <button v-for="item in organizations" :key="item.id" type="button" :class="[
                        'attempt-item',
                        'organization-history-item',
                        { active: item.id === selectedOrganizationId }
                    ]" @click="selectOrganization(item.id, 1)">
                        <div>
                            <strong>{{ item.name || 'Без названия' }}</strong>
                            <small>{{ item.reviews_count }} отзывов</small>
                        </div>
                    </button>
                </div>
            </section>
            <section class="stats-grid">
                <article class="stat-card organization-card">
                    <span class="stat-label">Организация</span>
                    <h2>{{ organization?.name || 'Организация не загружена' }}</h2>
                    <p>{{ organization?.yandex_url || 'Ссылка ещё не сохранена' }}</p>

                    <div class="status-row">
                        <span :class="['status-badge', organization?.parse_status]">
                            {{ statusLabel(organization?.parse_status) }}
                        </span>

                        <small>
                            Обновлено: {{ formatDate(organization?.last_parsed_at) }}
                        </small>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-label">Рейтинг</span>
                    <strong class="big-number">
                        {{ organization?.rating ?? '—' }}
                    </strong>
                    <p>Средняя оценка организации</p>
                </article>

                <article class="stat-card">
                    <span class="stat-label">Оценки</span>
                    <strong class="big-number">
                        {{ organization?.ratings_count ?? 0 }}
                    </strong>
                    <p>Всего оценок</p>
                </article>

                <article class="stat-card">
                    <span class="stat-label">Отзывы</span>
                    <strong class="big-number">
                        {{ organization?.reviews_count ?? 0 }}
                    </strong>
                    <p>
                        Загружено {{ organization?.saved_reviews_count ?? 0 }}
                        из {{ organization?.reviews_count ?? 0 }}
                    </p>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel">
                    <div class="panel-header">
                        <h2>История парсинга</h2>
                        <span>{{ attempts.length }} попыток</span>
                    </div>

                    <div v-if="attempts.length === 0" class="empty-state">
                        Истории запусков пока нет.
                    </div>

                    <div v-else class="attempt-list">
                        <div v-for="attempt in attempts" :key="attempt.id" class="attempt-item">
                            <span :class="['status-badge', attempt.status]">
                                {{ statusLabel(attempt.status) }}
                            </span>

                            <div>
                                <strong>
                                    {{ attempt.reviews_collected }} / {{ attempt.reviews_requested_limit }}
                                </strong>
                                <small>{{ formatDate(attempt.started_at) }}</small>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="panel reviews-panel">
                    <div class="panel-header">
                        <h2>Отзывы</h2>
                        <span>
                            Страница {{ meta?.current_page || 1 }} из {{ meta?.last_page || 1 }}
                        </span>
                    </div>

                    <div v-if="reviews.length === 0" class="empty-state">
                        Отзывы пока не загружены.
                    </div>

                    <div v-else class="review-list">
                        <article v-for="review in reviews" :key="review.id" class="review-card">
                            <div class="review-header">
                                <div>
                                    <strong>{{ review.author_name }}</strong>
                                    <small>{{ formatDate(review.reviewed_at) }}</small>
                                </div>

                                <span class="rating-pill">
                                    {{ review.rating || '—' }} ⭐
                                </span>
                            </div>

                            <p>{{ review.text || 'Без текста' }}</p>
                        </article>
                    </div>

                    <div v-if="meta && meta.last_page > 1" class="pagination">
                        <button :disabled="currentPage <= 1" @click="selectedOrganizationId
                            ? selectOrganization(selectedOrganizationId, currentPage - 1)
                            : loadDashboard(currentPage - 1)">
                            Назад
                        </button>

                        <button v-for="page in meta.last_page" :key="page" :class="{ active: page === currentPage }"
                            @click="selectedOrganizationId
                                ? selectOrganization(selectedOrganizationId, page)
                                : loadDashboard(page)">
                            {{ page }}
                        </button>

                        <button :disabled="currentPage >= meta.last_page" @click="selectedOrganizationId
                            ? selectOrganization(selectedOrganizationId, currentPage + 1)
                            : loadDashboard(currentPage + 1)">
                            Вперёд
                        </button>
                    </div>
                </article>
            </section>
        </template>
    </main>
</template>
