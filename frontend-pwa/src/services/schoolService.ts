import ApiService from './api';
import { ApiResponse, PaginatedResponse } from '../types';

// School-related types
export interface School {
  id: string;
  name: string;
  address: string;
  phone: string;
  email: string;
  website?: string;
  logo?: string;
  settings: SchoolSettings;
  created_at: string;
  updated_at: string;
}

export interface SchoolSettings {
  academic_year_start: string;
  academic_year_end: string;
  currency: string;
  timezone: string;
  language: string;
  theme: 'light' | 'dark';
  features: {
    financial_management: boolean;
    sports_management: boolean;
    medical_records: boolean;
    calendar: boolean;
    notifications: boolean;
    reports: boolean;
  };
}

export interface CreateSchoolData {
  name: string;
  address: string;
  phone: string;
  email: string;
  website?: string;
  settings?: Partial<SchoolSettings>;
}

export interface UpdateSchoolData extends Partial<CreateSchoolData> {
  logo?: File;
}

export class SchoolService {
  // Get current school information
  static async getCurrentSchool(): Promise<School> {
    const response = await ApiService.get<School>('/school/current');
    return response.data;
  }

  // Get school by ID
  static async getSchool(schoolId: string): Promise<School> {
    const response = await ApiService.get<School>(`/schools/${schoolId}`);
    return response.data;
  }

  // Get all schools (for admin users)
  static async getSchools(params?: {
    page?: number;
    per_page?: number;
    search?: string;
  }): Promise<PaginatedResponse<School>> {
    const response = await ApiService.get<PaginatedResponse<School>>('/schools', {
      params,
    });
    return response.data;
  }

  // Create new school
  static async createSchool(data: CreateSchoolData): Promise<School> {
    const response = await ApiService.post<School>('/schools', data);
    return response.data;
  }

  // Update school information
  static async updateSchool(
    schoolId: string,
    data: UpdateSchoolData
  ): Promise<School> {
    const formData = new FormData();
    
    Object.entries(data).forEach(([key, value]) => {
      if (value !== undefined) {
        if (key === 'logo' && value instanceof File) {
          formData.append(key, value);
        } else if (typeof value === 'object') {
          formData.append(key, JSON.stringify(value));
        } else {
          formData.append(key, String(value));
        }
      }
    });

    const response = await ApiService.post<School>(
      `/schools/${schoolId}`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  }

  // Update school settings
  static async updateSchoolSettings(
    schoolId: string,
    settings: Partial<SchoolSettings>
  ): Promise<SchoolSettings> {
    const response = await ApiService.put<SchoolSettings>(
      `/schools/${schoolId}/settings`,
      settings
    );
    return response.data;
  }

  // Delete school
  static async deleteSchool(schoolId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/schools/${schoolId}`);
  }

  // Upload school logo
  static async uploadLogo(schoolId: string, logo: File): Promise<School> {
    const formData = new FormData();
    formData.append('logo', logo);

    const response = await ApiService.post<School>(
      `/schools/${schoolId}/logo`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  }

  // Get school statistics
  static async getSchoolStats(schoolId: string): Promise<{
    total_students: number;
    total_teachers: number;
    total_staff: number;
    active_courses: number;
    pending_payments: number;
    upcoming_events: number;
  }> {
    const response = await ApiService.get(`/schools/${schoolId}/stats`);
    return response.data;
  }
}

export default SchoolService;