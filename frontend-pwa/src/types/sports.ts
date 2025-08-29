// Tipos base
export interface BaseEntity {
  id: string;
  created_at: string;
  updated_at: string;
}

// Tipos de respuesta paginada
export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

// Categorías
export interface Category extends BaseEntity {
  school_id: string;
  name: string;
  description?: string;
  age_group: string;
  min_age: number;
  max_age: number;
  gender: 'male' | 'female' | 'mixed';
  max_players: number;
  is_active: boolean;
  season_start?: string;
  season_end?: string;
  training_days?: string[];
  training_time?: string;
  coach_id?: string;
  // Relaciones
  coach?: {
    id: string;
    name: string;
    email: string;
  };
  players_count?: number;
  active_players_count?: number;
  trainings_count?: number;
}

export interface CreateCategoryData {
  name: string;
  description?: string;
  age_group: string;
  min_age: number;
  max_age: number;
  gender: 'male' | 'female' | 'mixed';
  max_players: number;
  season_start?: string;
  season_end?: string;
  training_days?: string[];
  training_time?: string;
  coach_id?: string;
}

export interface UpdateCategoryData extends Partial<CreateCategoryData> {
  is_active?: boolean;
}

export interface CategoryFilters {
  search?: string;
  age_group?: string;
  gender?: 'male' | 'female' | 'mixed';
  is_active?: boolean;
  coach_id?: string;
  page?: number;
  per_page?: number;
}

// Jugadores
export interface Player extends BaseEntity {
  school_id: string;
  category_id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  date_of_birth: string;
  age: number;
  gender: 'male' | 'female';
  document_type: 'dni' | 'passport' | 'other';
  document_number: string;
  phone?: string;
  email?: string;
  address?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  medical_conditions?: string;
  jersey_number?: number;
  position?: string;
  dominant_foot?: 'left' | 'right' | 'both';
  height?: number;
  weight?: number;
  photo_url?: string;
  is_active: boolean;
  registration_date: string;
  // Relaciones
  category?: Category;
  attendance_rate?: number;
  total_trainings?: number;
  attended_trainings?: number;
}

export interface CreatePlayerData {
  category_id: string;
  first_name: string;
  last_name: string;
  date_of_birth: string;
  gender: 'male' | 'female';
  document_type: 'dni' | 'passport' | 'other';
  document_number: string;
  phone?: string;
  email?: string;
  address?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  medical_conditions?: string;
  jersey_number?: number;
  position?: string;
  dominant_foot?: 'left' | 'right' | 'both';
  height?: number;
  weight?: number;
}

export interface UpdatePlayerData extends Partial<CreatePlayerData> {
  is_active?: boolean;
}

export interface PlayerFilters {
  search?: string;
  category_id?: string;
  age_min?: number;
  age_max?: number;
  gender?: 'male' | 'female';
  position?: string;
  is_active?: boolean;
  page?: number;
  per_page?: number;
}

export interface PlayerStats {
  total_trainings: number;
  attended_trainings: number;
  absent_trainings: number;
  late_trainings: number;
  excused_trainings: number;
  attendance_rate: number;
  current_streak: number;
  longest_streak: number;
  recent_attendance: {
    date: string;
    status: AttendanceStatus;
    training_type: string;
  }[];
}

// Entrenamientos
export type TrainingType = 'practice' | 'match' | 'friendly' | 'tournament' | 'physical' | 'tactical' | 'technical';
export type TrainingStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled';

export interface Training extends BaseEntity {
  school_id: string;
  category_id: string;
  date: string;
  start_time: string;
  end_time: string;
  location: string;
  type: TrainingType;
  status: TrainingStatus;
  objectives?: string;
  activities?: string;
  observations?: string;
  weather_conditions?: {
    temperature?: number;
    humidity?: number;
    conditions?: string;
  };
  duration_minutes?: number;
  coach_id?: string;
  // Relaciones
  category?: Category;
  coach?: {
    id: string;
    name: string;
    email: string;
  };
  attendance_stats?: {
    total_players: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    pending: number;
    attendance_rate: number;
  };
  attendances?: Attendance[];
}

export interface CreateTrainingData {
  category_id: string;
  date: string;
  start_time: string;
  end_time: string;
  location: string;
  type: TrainingType;
  objectives?: string;
  activities?: string;
  coach_id?: string;
}

export interface UpdateTrainingData extends Partial<CreateTrainingData> {
  status?: TrainingStatus;
  observations?: string;
  weather_conditions?: {
    temperature?: number;
    humidity?: number;
    conditions?: string;
  };
  duration_minutes?: number;
}

export interface CompleteTrainingData {
  observations?: string;
  weather_conditions?: {
    temperature?: number;
    humidity?: number;
    conditions?: string;
  };
  duration_minutes?: number;
  attendance?: {
    player_id: string;
    status: AttendanceStatus;
    arrival_time?: string;
    notes?: string;
  }[];
}

export interface TrainingFilters {
  search?: string;
  category_id?: string;
  date_from?: string;
  date_to?: string;
  type?: TrainingType;
  status?: TrainingStatus;
  coach_id?: string;
  location?: string;
  page?: number;
  per_page?: number;
}

// Asistencias
export type AttendanceStatus = 'pending' | 'present' | 'absent' | 'late' | 'excused';

export interface Attendance extends BaseEntity {
  school_id: string;
  training_id: string;
  player_id: string;
  date: string;
  status: AttendanceStatus;
  status_label: string;
  arrival_time?: string;
  notes?: string;
  recorded_by?: string;
  recorded_at?: string;
  is_late: boolean;
  late_duration_minutes?: number;
  // Relaciones
  training?: {
    id: string;
    date: string;
    start_time: string;
    end_time: string;
    location: string;
    type: TrainingType;
    category?: {
      id: string;
      name: string;
      age_group: string;
    };
  };
  player?: {
    id: string;
    first_name: string;
    last_name: string;
    full_name: string;
    jersey_number?: number;
    position?: string;
  };
  recorded_by_user?: {
    id: string;
    name: string;
    email: string;
  };
}

export interface UpdateAttendanceData {
  status: AttendanceStatus;
  arrival_time?: string;
  notes?: string;
}

export interface BulkAttendanceData {
  attendances: {
    id: string;
    status: AttendanceStatus;
    arrival_time?: string;
    notes?: string;
  }[];
}

export interface AttendanceFilters {
  training_id?: string;
  player_id?: string;
  category_id?: string;
  status?: AttendanceStatus;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

export interface AttendanceStats {
  total: number;
  present: number;
  absent: number;
  late: number;
  excused: number;
  pending: number;
  attendance_rate: number;
  period_days: number;
  recent_trend: 'improving' | 'declining' | 'stable';
}

// Estado del módulo deportivo
export interface SportsState {
  categories: Category[];
  players: Player[];
  trainings: Training[];
  attendances: Attendance[];
  selectedCategory: Category | null;
  selectedPlayer: Player | null;
  selectedTraining: Training | null;
  filters: SportsFilters;
  isLoading: boolean;
  error: string | null;
}

export interface SportsFilters {
  categoryFilters: CategoryFilters;
  playerFilters: PlayerFilters;
  trainingFilters: TrainingFilters;
  attendanceFilters: AttendanceFilters;
}

// Dashboard deportivo
export interface SportsDashboardData {
  summary: {
    total_categories: number;
    active_categories: number;
    total_players: number;
    active_players: number;
    todays_trainings: number;
    upcoming_trainings: number;
    overall_attendance_rate: number;
  };
  todays_trainings: Training[];
  upcoming_trainings: Training[];
  attendance_trends: {
    date: string;
    attendance_rate: number;
    total_trainings: number;
  }[];
  top_players: {
    player: Player;
    attendance_rate: number;
    total_trainings: number;
  }[];
  category_activity: {
    category: Category;
    trainings_count: number;
    avg_attendance_rate: number;
    active_players: number;
  }[];
  recent_activities: {
    id: string;
    type: 'training_created' | 'training_completed' | 'player_registered' | 'attendance_updated';
    description: string;
    timestamp: string;
    related_entity: {
      type: 'category' | 'player' | 'training';
      id: string;
      name: string;
    };
  }[];
}

// Tipos de formularios
export interface CategoryFormData extends CreateCategoryData {}
export interface PlayerFormData extends CreatePlayerData {}
export interface TrainingFormData extends CreateTrainingData {}
export interface AttendanceFormData extends UpdateAttendanceData {}

// Tipos de validación
export interface ValidationError {
  field: string;
  message: string;
}

export interface FormErrors {
  [key: string]: string | undefined;
}

// Tipos de eventos
export interface SportsEvent {
  type: 'category_created' | 'category_updated' | 'player_registered' | 'training_scheduled' | 'training_completed' | 'attendance_updated';
  payload: any;
  timestamp: string;
}

// Tipos de notificaciones
export interface SportsNotification {
  id: string;
  type: 'info' | 'success' | 'warning' | 'error';
  title: string;
  message: string;
  timestamp: string;
  read: boolean;
  actions?: {
    label: string;
    action: () => void;
  }[];
}
