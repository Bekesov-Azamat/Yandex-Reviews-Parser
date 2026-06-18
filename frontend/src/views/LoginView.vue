<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { login } from '../services/api'

const router = useRouter()

const email = ref('admin@example.com')
const password = ref('password')
const isLoading = ref(false)
const errorMessage = ref('')

async function submitLogin() {
    errorMessage.value = ''
    isLoading.value = true

    try {
        await login(email.value, password.value)
        await router.push('/dashboard')
    } catch (error) {
        errorMessage.value = error.message || 'Ошибка авторизации'
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <main class="login-page">
        <section class="login-shell">
            <div class="login-preview">
                <div class="brand-badge">
                    <span class="brand-dot"></span>
                    Yandex Reviews Parser
                </div>

                <h1>Отзывы организации в одном интерфейсе</h1>

                <p>
                    Прототип сервиса для загрузки данных карточки организации,
                    рейтинга и отзывов из Яндекс.Карт.
                </p>

                <div class="preview-card">
                    <div class="preview-card-header">
                        <div>
                            <span class="preview-label">Demo Organization</span>
                            <strong>4.32 ⭐</strong>
                        </div>

                        <span class="status-pill">success</span>
                    </div>

                    <div class="preview-stats">
                        <div>
                            <strong>245</strong>
                            <span>оценок</span>
                        </div>

                        <div>
                            <strong>137</strong>
                            <span>отзывов</span>
                        </div>

                        <div>
                            <strong>50</strong>
                            <span>на странице</span>
                        </div>
                    </div>
                </div>
            </div>

            <form class="login-card" @submit.prevent="submitLogin">
                <div class="login-card-header">
                    <span class="eyebrow">Вход в панель</span>
                    <h2>Авторизация</h2>
                    <p>Используется seed-пользователь из базы данных.</p>
                </div>

                <label class="form-field">
                    <span>Email</span>
                    <input v-model="email" type="email" autocomplete="email" placeholder="admin@example.com">
                </label>

                <label class="form-field">
                    <span>Пароль</span>
                    <input v-model="password" type="password" autocomplete="current-password" placeholder="password">
                </label>

                <div v-if="errorMessage" class="error-box">
                    {{ errorMessage }}
                </div>

                <button class="primary-button" type="submit" :disabled="isLoading">
                    <span v-if="isLoading">Входим...</span>
                    <span v-else>Войти</span>
                </button>

                <p class="hint">
                    Demo: admin@example.com / password
                </p>
            </form>
        </section>
    </main>
</template>
