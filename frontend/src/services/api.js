const API_BASE_URL = 'http://localhost:8000'

function getCookie(name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)

    if (parts.length !== 2) {
        return null
    }

    return decodeURIComponent(parts.pop().split(';').shift())
}

async function request(path, options = {}) {
    const xsrfToken = getCookie('XSRF-TOKEN')

    const response = await fetch(`${API_BASE_URL}${path}`, {
        credentials: 'include',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
            ...(options.headers || {}),
        },
        ...options,
    })

    const data = await response.json().catch(() => null)

    if (!response.ok) {
        const message = data?.message || data?.error?.message || 'Request failed'
        throw new Error(message)
    }

    return data
}

export async function csrfCookie() {
    const response = await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
        credentials: 'include',
        headers: {
            Accept: 'application/json',
        },
    })

    if (!response.ok) {
        throw new Error('Не удалось получить CSRF cookie')
    }
}

export async function login(email, password) {
    await csrfCookie()

    return request('/api/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    })
}

export async function logout() {
    return request('/api/logout', {
        method: 'POST',
    })
}

export async function getCurrentUser() {
    try {
        const data = await request('/api/me')
        return data.user
    } catch {
        return null
    }
}

export async function getOrganization() {
    return request('/api/organization')
}

export async function saveOrganizationUrl(url) {
    return request('/api/organization', {
        method: 'POST',
        body: JSON.stringify({ url }),
    })
}

export async function getReviews(page = 1) {
    return request(`/api/organization/reviews?page=${page}`)
}

export async function getParseAttempts() {
    return request('/api/organization/parse-attempts')
}

export async function getHealth() {
    return request('/api/health')
}
