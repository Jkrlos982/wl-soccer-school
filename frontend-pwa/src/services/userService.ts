import ApiService from './api';
import { ApiResponse, PaginatedResponse, User } from '../types';

// Extended user types for school management
export interface Student extends User {
  student_id: string;
  grade: string;
  section: string;
  parent_contact: {
    father_name?: string;
    mother_name?: string;
    guardian_name?: string;
    phone: string;
    email?: string;
    address: string;
  };
  enrollment_date: string;
  status: 'active' | 'inactive' | 'graduated' | 'transferred';
  medical_info?: {
    allergies?: string[];
    medications?: string[];
    emergency_contact: string;
    blood_type?: string;
  };
}

export interface Teacher extends User {
  employee_id: string;
  department: string;
  subjects: string[];
  qualification: string;
  experience_years: number;
  hire_date: string;
  salary?: number;
  status: 'active' | 'inactive' | 'on_leave';
}

export interface Staff extends User {
  employee_id: string;
  department: string;
  position: string;
  hire_date: string;
  salary?: number;
  status: 'active' | 'inactive' | 'on_leave';
}

export interface CreateStudentData {
  name: string;
  email: string;
  password: string;
  grade: string;
  section: string;
  parent_contact: Student['parent_contact'];
  medical_info?: Student['medical_info'];
}

export interface CreateTeacherData {
  name: string;
  email: string;
  password: string;
  department: string;
  subjects: string[];
  qualification: string;
  experience_years: number;
  salary?: number;
}

export interface CreateStaffData {
  name: string;
  email: string;
  password: string;
  department: string;
  position: string;
  salary?: number;
}

export class UserService {
  // Student Management
  static async getStudents(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    grade?: string;
    section?: string;
    status?: string;
  }): Promise<PaginatedResponse<Student>> {
    const response = await ApiService.get<PaginatedResponse<Student>>('/students', {
      params,
    });
    return response.data;
  }

  static async getStudent(studentId: string): Promise<Student> {
    const response = await ApiService.get<Student>(`/students/${studentId}`);
    return response.data;
  }

  static async createStudent(data: CreateStudentData): Promise<Student> {
    const response = await ApiService.post<Student>('/students', data);
    return response.data;
  }

  static async updateStudent(
    studentId: string,
    data: Partial<CreateStudentData>
  ): Promise<Student> {
    const response = await ApiService.put<Student>(`/students/${studentId}`, data);
    return response.data;
  }

  static async deleteStudent(studentId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/students/${studentId}`);
  }

  // Teacher Management
  static async getTeachers(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    department?: string;
    status?: string;
  }): Promise<PaginatedResponse<Teacher>> {
    const response = await ApiService.get<PaginatedResponse<Teacher>>('/teachers', {
      params,
    });
    return response.data;
  }

  static async getTeacher(teacherId: string): Promise<Teacher> {
    const response = await ApiService.get<Teacher>(`/teachers/${teacherId}`);
    return response.data;
  }

  static async createTeacher(data: CreateTeacherData): Promise<Teacher> {
    const response = await ApiService.post<Teacher>('/teachers', data);
    return response.data;
  }

  static async updateTeacher(
    teacherId: string,
    data: Partial<CreateTeacherData>
  ): Promise<Teacher> {
    const response = await ApiService.put<Teacher>(`/teachers/${teacherId}`, data);
    return response.data;
  }

  static async deleteTeacher(teacherId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/teachers/${teacherId}`);
  }

  // Staff Management
  static async getStaff(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    department?: string;
    status?: string;
  }): Promise<PaginatedResponse<Staff>> {
    const response = await ApiService.get<PaginatedResponse<Staff>>('/staff', {
      params,
    });
    return response.data;
  }

  static async getStaffMember(staffId: string): Promise<Staff> {
    const response = await ApiService.get<Staff>(`/staff/${staffId}`);
    return response.data;
  }

  static async createStaff(data: CreateStaffData): Promise<Staff> {
    const response = await ApiService.post<Staff>('/staff', data);
    return response.data;
  }

  static async updateStaff(
    staffId: string,
    data: Partial<CreateStaffData>
  ): Promise<Staff> {
    const response = await ApiService.put<Staff>(`/staff/${staffId}`, data);
    return response.data;
  }

  static async deleteStaff(staffId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/staff/${staffId}`);
  }

  // Bulk Operations
  static async bulkImportStudents(file: File): Promise<ApiResponse> {
    const formData = new FormData();
    formData.append('file', file);

    return await ApiService.post('/students/bulk-import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  }

  static async bulkImportTeachers(file: File): Promise<ApiResponse> {
    const formData = new FormData();
    formData.append('file', file);

    return await ApiService.post('/teachers/bulk-import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  }

  // User Profile Management
  static async uploadAvatar(userId: string, avatar: File): Promise<User> {
    const formData = new FormData();
    formData.append('avatar', avatar);

    const response = await ApiService.post<User>(
      `/users/${userId}/avatar`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  }

  static async changeUserStatus(
    userId: string,
    status: 'active' | 'inactive'
  ): Promise<ApiResponse> {
    return await ApiService.patch(`/users/${userId}/status`, { status });
  }

  // Get user statistics
  static async getUserStats(): Promise<{
    total_students: number;
    total_teachers: number;
    total_staff: number;
    active_users: number;
    new_registrations_this_month: number;
  }> {
    const response = await ApiService.get('/users/stats');
    return response.data;
  }
}

export default UserService;