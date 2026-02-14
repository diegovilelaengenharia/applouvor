/**
 * Reading API Client
 * Handles all communication with reading plan and progress APIs
 */

const ReadingAPI = {
    baseUrl: window.APP_URL || '',

    /**
     * Fetch available reading plans
     */
    async getPlans() {
        try {
            const response = await fetch(`${this.baseUrl}/api/reading_plan.php?action=get_plans`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar planos');
            }
            
            return data.plans;
        } catch (error) {
            console.error('Error fetching plans:', error);
            throw error;
        }
    },

    /**
     * Fetch specific plan details with all reading data
     */
    async getPlanDetails(planId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/reading_plan.php?action=get_plan_details&plan_id=${planId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar detalhes do plano');
            }
            
            return data.plan;
        } catch (error) {
            console.error('Error fetching plan details:', error);
            throw error;
        }
    },

    /**
     * Get user's reading settings (selected plan, start date)
     */
    async getUserSettings() {
        try {
            const response = await fetch(`${this.baseUrl}/api/reading_progress.php?action=get_user_settings`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar configurações');
            }
            
            return data.settings;
        } catch (error) {
            console.error('Error fetching user settings:', error);
            throw error;
        }
    },

    /**
     * Save user's reading plan selection
     */
    async saveUserSettings(planId, startDate) {
        try {
            const formData = new FormData();
            formData.append('action', 'save_user_settings');
            formData.append('plan_id', planId);
            formData.append('start_date', startDate);

            const response = await fetch(`${this.baseUrl}/api/reading_progress.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao salvar configurações');
            }
            
            return data;
        } catch (error) {
            console.error('Error saving user settings:', error);
            throw error;
        }
    },

    /**
     * Get user's reading progress for a specific plan
     */
    async getProgress(planId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/reading_progress.php?action=get_progress&plan_id=${planId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar progresso');
            }
            
            return data.progress;
        } catch (error) {
            console.error('Error fetching progress:', error);
            throw error;
        }
    },

    /**
     * Mark a passage as read/unread
     */
    async markPassage(planId, dayNumber, passageIndex, isCompleted) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_passage');
            formData.append('plan_id', planId);
            formData.append('day_number', dayNumber);
            formData.append('passage_index', passageIndex);
            formData.append('is_completed', isCompleted ? '1' : '0');

            const response = await fetch(`${this.baseUrl}/api/reading_progress.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao atualizar progresso');
            }
            
            return data;
        } catch (error) {
            console.error('Error marking passage:', error);
            throw error;
        }
    },

    /**
     * Save a reading note
     */
    async saveNote(planId, dayNumber, passageReference, noteText) {
        try {
            const formData = new FormData();
            formData.append('action', 'save_note');
            formData.append('plan_id', planId);
            formData.append('day_number', dayNumber);
            formData.append('passage_reference', passageReference);
            formData.append('note_text', noteText);

            const response = await fetch(`${this.baseUrl}/api/reading_progress.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao salvar anotação');
            }
            
            return data;
        } catch (error) {
            console.error('Error saving note:', error);
            throw error;
        }
    },

    /**
     * Get reading statistics
     */
    async getStats(planId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/reading_progress.php?action=get_stats&plan_id=${planId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar estatísticas');
            }
            
            return data.stats;
        } catch (error) {
            console.error('Error fetching stats:', error);
            throw error;
        }
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReadingAPI;
}
