import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import {
  SportsState,
  Category,
  CreateCategoryData,
  UpdateCategoryData,
  CategoryFilters,
  Player,
  CreatePlayerData,
  UpdatePlayerData,
  PlayerFilters,
  PlayerStats,
  Training,
  CreateTrainingData,
  UpdateTrainingData,
  CompleteTrainingData,
  TrainingFilters,
  Attendance,
  UpdateAttendanceData,
  BulkAttendanceData,
  AttendanceFilters,
  AttendanceStats,
  SportsDashboardData,
  PaginatedResponse,
  ApiError,
} from '../types';
import { sportsService } from '../services/sportsService';

// Extended state interface for better state management
interface ExtendedSportsState extends Omit<SportsState, 'categories' | 'players' | 'trainings' | 'attendances' | 'filters'> {
  // Paginated data
  categories: PaginatedResponse<Category>;
  players: PaginatedResponse<Player>;
  trainings: PaginatedResponse<Training>;
  attendances: PaginatedResponse<Attendance>;
  
  // Individual filters (flattened from SportsFilters)
  categoryFilters: CategoryFilters;
  playerFilters: PlayerFilters;
  trainingFilters: TrainingFilters;
  attendanceFilters: AttendanceFilters;
  
  // Additional state
  upcomingTrainings: Training[];
  playerStats: PlayerStats | null;
  attendanceStats: AttendanceStats | null;
  dashboardData: SportsDashboardData | null;
}

// Initial state
const initialState: ExtendedSportsState = {
  // Categories
  categories: {
    data: [],
    total: 0,
    current_page: 1,
    per_page: 10,
    last_page: 1,
    from: 0,
    to: 0,
    links: {
      first: '',
      last: '',
      prev: null,
      next: null,
    },
  },
  selectedCategory: null,
  categoryFilters: {
    search: '',
    is_active: true,
    page: 1,
    per_page: 10,
  },

  // Players
  players: {
    data: [],
    total: 0,
    current_page: 1,
    per_page: 10,
    last_page: 1,
    from: 0,
    to: 0,
    links: {
      first: '',
      last: '',
      prev: null,
      next: null,
    },
  },
  selectedPlayer: null,
  playerStats: null,
  playerFilters: {
    search: '',
    category_id: '',
    is_active: true,
    page: 1,
    per_page: 10,
  },

  // Trainings
  trainings: {
    data: [],
    total: 0,
    current_page: 1,
    per_page: 10,
    last_page: 1,
    from: 0,
    to: 0,
    links: {
      first: '',
      last: '',
      prev: null,
      next: null,
    },
  },
  selectedTraining: null,
  upcomingTrainings: [],
  trainingFilters: {
    search: '',
    category_id: '',
    status: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 10,
  },

  // Attendances
  attendances: {
    data: [],
    total: 0,
    current_page: 1,
    per_page: 10,
    last_page: 1,
    from: 0,
    to: 0,
    links: {
      first: '',
      last: '',
      prev: null,
      next: null,
    },
  },
  attendanceStats: null,
  attendanceFilters: {
    training_id: '',
    player_id: '',
    status: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 10,
  },

  // Dashboard
  dashboardData: null,

  // UI State
  isLoading: false,
  error: null,
};

// ==================== CATEGORIES ASYNC THUNKS ====================

export const fetchCategories = createAsyncThunk<
  PaginatedResponse<Category>,
  CategoryFilters | undefined,
  { rejectValue: ApiError }
>('sports/fetchCategories', async (filters, { rejectWithValue }) => {
  try {
    return await sportsService.getCategories(filters);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching categories',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchCategory = createAsyncThunk<
  Category,
  string,
  { rejectValue: ApiError }
>('sports/fetchCategory', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.getCategory(id);
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const createCategory = createAsyncThunk<
  Category,
  CreateCategoryData,
  { rejectValue: ApiError }
>('sports/createCategory', async (data, { rejectWithValue }) => {
  try {
    return await sportsService.createCategory(data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error creating category',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const updateCategory = createAsyncThunk<
  Category,
  { id: string; data: UpdateCategoryData },
  { rejectValue: ApiError }
>('sports/updateCategory', async ({ id, data }, { rejectWithValue }) => {
  try {
    return await sportsService.updateCategory(id, data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error updating category',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const deleteCategory = createAsyncThunk<
  string,
  string,
  { rejectValue: ApiError }
>('sports/deleteCategory', async (id, { rejectWithValue }) => {
  try {
    await sportsService.deleteCategory(id);
    return id;
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error deleting category',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

// ==================== PLAYERS ASYNC THUNKS ====================

export const fetchPlayers = createAsyncThunk<
  PaginatedResponse<Player>,
  PlayerFilters | undefined,
  { rejectValue: ApiError }
>('sports/fetchPlayers', async (filters, { rejectWithValue }) => {
  try {
    return await sportsService.getPlayers(filters);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching players',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchPlayer = createAsyncThunk<
  Player,
  string,
  { rejectValue: ApiError }
>('sports/fetchPlayer', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.getPlayer(id);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching player',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const createPlayer = createAsyncThunk<
  Player,
  CreatePlayerData,
  { rejectValue: ApiError }
>('sports/createPlayer', async (data, { rejectWithValue }) => {
  try {
    return await sportsService.createPlayer(data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error creating player',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const updatePlayer = createAsyncThunk<
  Player,
  { id: string; data: UpdatePlayerData },
  { rejectValue: ApiError }
>('sports/updatePlayer', async ({ id, data }, { rejectWithValue }) => {
  try {
    return await sportsService.updatePlayer(id, data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error updating player',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const deletePlayer = createAsyncThunk<
  string,
  string,
  { rejectValue: ApiError }
>('sports/deletePlayer', async (id, { rejectWithValue }) => {
  try {
    await sportsService.deletePlayer(id);
    return id;
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error deleting player',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchPlayerStats = createAsyncThunk<
  PlayerStats,
  string,
  { rejectValue: ApiError }
>('sports/fetchPlayerStats', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.getPlayerStats(id);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching player stats',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const uploadPlayerPhoto = createAsyncThunk<
  { photo_url: string },
  { id: string; file: File },
  { rejectValue: ApiError }
>('sports/uploadPlayerPhoto', async ({ id, file }, { rejectWithValue }) => {
  try {
    return await sportsService.uploadPlayerPhoto(id, file);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error uploading player photo',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

// ==================== TRAININGS ASYNC THUNKS ====================

export const fetchTrainings = createAsyncThunk<
  PaginatedResponse<Training>,
  TrainingFilters | undefined,
  { rejectValue: ApiError }
>('sports/fetchTrainings', async (filters, { rejectWithValue }) => {
  try {
    return await sportsService.getTrainings(filters);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching trainings',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchUpcomingTrainings = createAsyncThunk<
  Training[],
  void,
  { rejectValue: ApiError }
>('sports/fetchUpcomingTrainings', async (_, { rejectWithValue }) => {
  try {
    return await sportsService.getUpcomingTrainings();
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching upcoming trainings',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchTraining = createAsyncThunk<
  Training,
  string,
  { rejectValue: ApiError }
>('sports/fetchTraining', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.getTraining(id);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const createTraining = createAsyncThunk<
  Training,
  CreateTrainingData,
  { rejectValue: ApiError }
>('sports/createTraining', async (data, { rejectWithValue }) => {
  try {
    return await sportsService.createTraining(data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error creating training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const updateTraining = createAsyncThunk<
  Training,
  { id: string; data: UpdateTrainingData },
  { rejectValue: ApiError }
>('sports/updateTraining', async ({ id, data }, { rejectWithValue }) => {
  try {
    return await sportsService.updateTraining(id, data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error updating training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const deleteTraining = createAsyncThunk<
  string,
  string,
  { rejectValue: ApiError }
>('sports/deleteTraining', async (id, { rejectWithValue }) => {
  try {
    await sportsService.deleteTraining(id);
    return id;
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error deleting training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const startTraining = createAsyncThunk<
  Training,
  string,
  { rejectValue: ApiError }
>('sports/startTraining', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.startTraining(id);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error starting training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const completeTraining = createAsyncThunk<
  Training,
  { id: string; data: CompleteTrainingData },
  { rejectValue: ApiError }
>('sports/completeTraining', async ({ id, data }, { rejectWithValue }) => {
  try {
    return await sportsService.completeTraining(id, data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error completing training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const cancelTraining = createAsyncThunk<
  Training,
  string,
  { rejectValue: ApiError }
>('sports/cancelTraining', async (id, { rejectWithValue }) => {
  try {
    return await sportsService.cancelTraining(id);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error canceling training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

// ==================== ATTENDANCES ASYNC THUNKS ====================

export const fetchAttendances = createAsyncThunk<
  PaginatedResponse<Attendance>,
  AttendanceFilters | undefined,
  { rejectValue: ApiError }
>('sports/fetchAttendances', async (filters, { rejectWithValue }) => {
  try {
    return await sportsService.getAttendances(filters);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching attendances',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchAttendancesByTraining = createAsyncThunk<
  Attendance[],
  string,
  { rejectValue: ApiError }
>('sports/fetchAttendancesByTraining', async (trainingId, { rejectWithValue }) => {
  try {
    return await sportsService.getAttendancesByTraining(trainingId);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching attendances by training',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const updateAttendance = createAsyncThunk<
  Attendance,
  { id: string; data: UpdateAttendanceData },
  { rejectValue: ApiError }
>('sports/updateAttendance', async ({ id, data }, { rejectWithValue }) => {
  try {
    return await sportsService.updateAttendance(id, data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error updating attendance',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const bulkUpdateAttendance = createAsyncThunk<
  Attendance[],
  BulkAttendanceData,
  { rejectValue: ApiError }
>('sports/bulkUpdateAttendance', async (data, { rejectWithValue }) => {
  try {
    return await sportsService.bulkUpdateAttendance(data);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error bulk updating attendance',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

export const fetchPlayerAttendanceStats = createAsyncThunk<
  AttendanceStats,
  { playerId: string; period?: number },
  { rejectValue: ApiError }
>('sports/fetchPlayerAttendanceStats', async ({ playerId, period }, { rejectWithValue }) => {
  try {
    return await sportsService.getPlayerAttendanceStats(playerId, period);
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching player attendance stats',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

// ==================== DASHBOARD ASYNC THUNKS ====================

export const fetchDashboardData = createAsyncThunk<
  SportsDashboardData,
  void,
  { rejectValue: ApiError }
>('sports/fetchDashboardData', async (_, { rejectWithValue }) => {
  try {
    return await sportsService.getDashboardData();
  } catch (error: any) {
    return rejectWithValue({
      message: error.message || 'Error fetching dashboard data',
      status: error.status || 500,
      errors: error.errors || {},
    });
  }
});

// ==================== SLICE ====================

const sportsSlice = createSlice({
  name: 'sports',
  initialState,
  reducers: {
    // Clear error
    clearError: (state) => {
      state.error = null;
    },

    // Set filters
    setCategoryFilters: (state, action: PayloadAction<Partial<CategoryFilters>>) => {
      state.categoryFilters = { ...state.categoryFilters, ...action.payload };
    },
    setPlayerFilters: (state, action: PayloadAction<Partial<PlayerFilters>>) => {
      state.playerFilters = { ...state.playerFilters, ...action.payload };
    },
    setTrainingFilters: (state, action: PayloadAction<Partial<TrainingFilters>>) => {
      state.trainingFilters = { ...state.trainingFilters, ...action.payload };
    },
    setAttendanceFilters: (state, action: PayloadAction<Partial<AttendanceFilters>>) => {
      state.attendanceFilters = { ...state.attendanceFilters, ...action.payload };
    },

    // Set selected items
    setSelectedCategory: (state, action: PayloadAction<Category | null>) => {
      state.selectedCategory = action.payload;
    },
    setSelectedPlayer: (state, action: PayloadAction<Player | null>) => {
      state.selectedPlayer = action.payload;
    },
    setSelectedTraining: (state, action: PayloadAction<Training | null>) => {
      state.selectedTraining = action.payload;
    },

    // Clear data
    clearCategories: (state) => {
      state.categories = initialState.categories;
      state.selectedCategory = null;
    },
    clearPlayers: (state) => {
      state.players = initialState.players;
      state.selectedPlayer = null;
      state.playerStats = null;
    },
    clearTrainings: (state) => {
      state.trainings = initialState.trainings;
      state.selectedTraining = null;
      state.upcomingTrainings = [];
    },
    clearAttendances: (state) => {
      state.attendances = initialState.attendances;
      state.attendanceStats = null;
    },
    clearDashboard: (state) => {
      state.dashboardData = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // ==================== CATEGORIES ====================
      .addCase(fetchCategories.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchCategories.fulfilled, (state, action) => {
        state.isLoading = false;
        state.categories = action.payload;
      })
      .addCase(fetchCategories.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload || null;
      })
      .addCase(fetchCategory.fulfilled, (state, action) => {
        state.selectedCategory = action.payload;
      })
      .addCase(createCategory.fulfilled, (state, action) => {
        state.categories.data.unshift(action.payload);
        state.categories.total += 1;
      })
      .addCase(updateCategory.fulfilled, (state, action) => {
        const index = state.categories.data.findIndex(cat => cat.id === action.payload.id);
        if (index !== -1) {
          state.categories.data[index] = action.payload;
        }
        if (state.selectedCategory?.id === action.payload.id) {
          state.selectedCategory = action.payload;
        }
      })
      .addCase(deleteCategory.fulfilled, (state, action) => {
        state.categories.data = state.categories.data.filter(cat => cat.id !== action.payload);
        state.categories.total -= 1;
        if (state.selectedCategory?.id === action.payload) {
          state.selectedCategory = null;
        }
      })

      // ==================== PLAYERS ====================
      .addCase(fetchPlayers.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchPlayers.fulfilled, (state, action) => {
        state.isLoading = false;
        state.players = action.payload;
      })
      .addCase(fetchPlayers.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload || null;
      })
      .addCase(fetchPlayer.fulfilled, (state, action) => {
        state.selectedPlayer = action.payload;
      })
      .addCase(createPlayer.fulfilled, (state, action) => {
        state.players.data.unshift(action.payload);
        state.players.total += 1;
      })
      .addCase(updatePlayer.fulfilled, (state, action) => {
        const index = state.players.data.findIndex(player => player.id === action.payload.id);
        if (index !== -1) {
          state.players.data[index] = action.payload;
        }
        if (state.selectedPlayer?.id === action.payload.id) {
          state.selectedPlayer = action.payload;
        }
      })
      .addCase(deletePlayer.fulfilled, (state, action) => {
        state.players.data = state.players.data.filter(player => player.id !== action.payload);
        state.players.total -= 1;
        if (state.selectedPlayer?.id === action.payload) {
          state.selectedPlayer = null;
        }
      })
      .addCase(fetchPlayerStats.fulfilled, (state, action) => {
        state.playerStats = action.payload;
      })
      .addCase(uploadPlayerPhoto.fulfilled, (state, action) => {
        if (state.selectedPlayer) {
          state.selectedPlayer.photo_url = action.payload.photo_url;
        }
        // Update in players list if exists
        const playerIndex = state.players.data.findIndex(p => p.id === state.selectedPlayer?.id);
        if (playerIndex !== -1) {
          state.players.data[playerIndex].photo_url = action.payload.photo_url;
        }
      })

      // ==================== TRAININGS ====================
      .addCase(fetchTrainings.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchTrainings.fulfilled, (state, action) => {
        state.isLoading = false;
        state.trainings = action.payload;
      })
      .addCase(fetchTrainings.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload || null;
      })
      .addCase(fetchUpcomingTrainings.fulfilled, (state, action) => {
        state.upcomingTrainings = action.payload;
      })
      .addCase(fetchTraining.fulfilled, (state, action) => {
        state.selectedTraining = action.payload;
      })
      .addCase(createTraining.fulfilled, (state, action) => {
        state.trainings.data.unshift(action.payload);
        state.trainings.total += 1;
      })
      .addCase(updateTraining.fulfilled, (state, action) => {
        const index = state.trainings.data.findIndex(training => training.id === action.payload.id);
        if (index !== -1) {
          state.trainings.data[index] = action.payload;
        }
        if (state.selectedTraining?.id === action.payload.id) {
          state.selectedTraining = action.payload;
        }
      })
      .addCase(deleteTraining.fulfilled, (state, action) => {
        state.trainings.data = state.trainings.data.filter(training => training.id !== action.payload);
        state.trainings.total -= 1;
        if (state.selectedTraining?.id === action.payload) {
          state.selectedTraining = null;
        }
      })
      .addCase(startTraining.fulfilled, (state, action) => {
        const index = state.trainings.data.findIndex(training => training.id === action.payload.id);
        if (index !== -1) {
          state.trainings.data[index] = action.payload;
        }
        if (state.selectedTraining?.id === action.payload.id) {
          state.selectedTraining = action.payload;
        }
      })
      .addCase(completeTraining.fulfilled, (state, action) => {
        const index = state.trainings.data.findIndex(training => training.id === action.payload.id);
        if (index !== -1) {
          state.trainings.data[index] = action.payload;
        }
        if (state.selectedTraining?.id === action.payload.id) {
          state.selectedTraining = action.payload;
        }
      })
      .addCase(cancelTraining.fulfilled, (state, action) => {
        const index = state.trainings.data.findIndex(training => training.id === action.payload.id);
        if (index !== -1) {
          state.trainings.data[index] = action.payload;
        }
        if (state.selectedTraining?.id === action.payload.id) {
          state.selectedTraining = action.payload;
        }
      })

      // ==================== ATTENDANCES ====================
      .addCase(fetchAttendances.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchAttendances.fulfilled, (state, action) => {
        state.isLoading = false;
        state.attendances = action.payload;
      })
      .addCase(fetchAttendances.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload || null;
      })
      .addCase(updateAttendance.fulfilled, (state, action) => {
        const index = state.attendances.data.findIndex(att => att.id === action.payload.id);
        if (index !== -1) {
          state.attendances.data[index] = action.payload;
        }
      })
      .addCase(bulkUpdateAttendance.fulfilled, (state, action) => {
        action.payload.forEach(updatedAttendance => {
          const index = state.attendances.data.findIndex(att => att.id === updatedAttendance.id);
          if (index !== -1) {
            state.attendances.data[index] = updatedAttendance;
          }
        });
      })
      .addCase(fetchPlayerAttendanceStats.fulfilled, (state, action) => {
        state.attendanceStats = action.payload;
      })

      // ==================== DASHBOARD ====================
      .addCase(fetchDashboardData.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchDashboardData.fulfilled, (state, action) => {
        state.isLoading = false;
        state.dashboardData = action.payload;
      })
      .addCase(fetchDashboardData.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload || null;
      });
  },
});

export const {
  clearError,
  setCategoryFilters,
  setPlayerFilters,
  setTrainingFilters,
  setAttendanceFilters,
  setSelectedCategory,
  setSelectedPlayer,
  setSelectedTraining,
  clearCategories,
  clearPlayers,
  clearTrainings,
  clearAttendances,
  clearDashboard,
} = sportsSlice.actions;

export default sportsSlice.reducer;