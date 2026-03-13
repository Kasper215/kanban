import axios from 'axios'
import { useKanbanStore } from '@/stores/useKanbanStore.js'

export async function apiRequest(method, url, payload = {}, options = {}) {
    const store = useKanbanStore()

    // добавляем board_uuid во все запросы
    const data = {
        ...payload,
        board_uuid: store.board?.uuid
    }

    return axios({
        method,
        url,
        data,
        ...options
    })
}
