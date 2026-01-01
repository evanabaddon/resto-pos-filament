import axios from 'axios';

// @ts-ignore
// @ts-ignore
const DEFAULT_API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const STORED_API_URL = localStorage.getItem('pos_api_url');

// @ts-ignore
const API_TOKEN = import.meta.env.VITE_API_TOKEN || 'test-token';

const client = axios.create({
    baseURL: STORED_API_URL || DEFAULT_API_URL,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${API_TOKEN}`
    }
});

export const updateApiConfig = (url: string) => {
    localStorage.setItem('pos_api_url', url);
    client.defaults.baseURL = url;
};


// Request Interceptor
client.interceptors.request.use(req => {
    console.log(`📡 API Request: ${req.method?.toUpperCase()} ${req.url}`);
    return req;
});

// Response Interceptor
client.interceptors.response.use(
    res => {
        console.log(`✅ API Response: ${res.status} ${res.config.url}`);
        return res;
    },
    err => {
        console.error(`❌ API Error: ${err.message}`, err.response?.data);
        return Promise.reject(err);
    }
);

export const api = {
    // Sync
    getProducts: () => client.get('/pos/products-sync'),
    syncOfflineSale: (data: any) => client.post('/pos/sync-offline-sales', data),

    // Members
    searchMembers: (query: string) => client.get(`/members/search`, { params: { q: query } }),
    createMember: (data: any) => client.post('/members', data),

    // Drafts
    getDrafts: () => client.get('/pos/drafts'),
    deleteDraft: (id: number) => client.delete(`/pos/drafts/${id}`),

    // System
    testConnection: (url: string) => axios.get(`${url}/pos/status`, { timeout: 5000 }),
    getSettings: () => client.get('/pos/settings'),

    // Shifts
    syncShifts: (shifts: any[]) => client.post('/pos/sync-shifts', { shifts }),
    getCurrentShift: () => client.get('/pos/current-shift'),
    getSalesHistory: () => client.get('/pos/sales-history'),
};
