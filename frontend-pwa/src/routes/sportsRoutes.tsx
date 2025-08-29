import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import {
  SportsDashboard,
  CategoriesList,
  CategoryForm,
  CategoryDetail,
  PlayersList,
  PlayerForm,
  PlayerDetail,
  TrainingsList,
  TrainingForm,
  TrainingDetail,
  TrainingCalendar,
  UpcomingTrainings,
  AttendanceList,
  AttendanceForm,
  AttendanceStats,
  AttendanceReport
} from '../components/sports';
import { AttendanceTrackerWrapper } from '../components/sports/wrappers';
import { SportsLayout } from '../components/sports/layout';

const SportsRoutes: React.FC = () => {
  return (
    <Routes>
      <Route path="/*" element={<SportsLayout />}>
        {/* Dashboard */}
        <Route index element={<SportsDashboard />} />
        <Route path="dashboard" element={<Navigate to="/sports" replace />} />
        
        {/* Categories Routes */}
        <Route path="categories" element={<CategoriesList />} />
        <Route path="categories/new" element={<CategoryForm />} />
        <Route path="categories/:id" element={<CategoryDetail />} />
        <Route path="categories/:id/edit" element={<CategoryForm />} />
        
        {/* Players Routes */}
        <Route path="players" element={<PlayersList />} />
        <Route path="players/new" element={<PlayerForm />} />
        <Route path="players/:id" element={<PlayerDetail />} />
        <Route path="players/:id/edit" element={<PlayerForm />} />
        {/* Player stats can be accessed through player detail */}
        
        {/* Trainings Routes */}
        <Route path="trainings" element={<TrainingsList />} />
        <Route path="trainings/new" element={<TrainingForm />} />
        <Route path="trainings/:id" element={<TrainingDetail trainingId={window.location.pathname.split('/').pop() || ''} />} />
        <Route path="trainings/:id/edit" element={<TrainingForm />} />
        <Route path="trainings/calendar" element={<TrainingCalendar />} />
        <Route path="trainings/upcoming" element={<UpcomingTrainings />} />
        
        {/* Attendance Routes */}
        <Route path="attendances" element={<AttendanceList />} />
        <Route path="attendances/new" element={<AttendanceForm />} />
        <Route path="attendances/:id/edit" element={<AttendanceForm />} />
        <Route path="attendances/tracker/:trainingId" element={<AttendanceTrackerWrapper />} />
        <Route path="attendances/stats" element={<AttendanceStats />} />
        <Route path="attendances/reports" element={<AttendanceReport />} />
        
        {/* Fallback */}
        <Route path="*" element={<Navigate to="/sports" replace />} />
      </Route>
    </Routes>
  );
};

export default SportsRoutes;