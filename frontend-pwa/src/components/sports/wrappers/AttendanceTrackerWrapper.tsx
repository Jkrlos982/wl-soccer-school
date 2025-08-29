import React, { useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchTraining } from '../../../store/sportsSlice';
import { AttendanceTracker } from '../attendances';

const AttendanceTrackerWrapper: React.FC = () => {
  const { trainingId } = useParams<{ trainingId: string }>();
  const dispatch = useAppDispatch();
  const { currentTraining, isLoading } = useAppSelector((state: any) => ({
    currentTraining: state.sports.currentTraining,
    isLoading: state.sports.isLoading
  }));
  
  useEffect(() => {
    if (trainingId) {
      dispatch(fetchTraining(trainingId));
    }
  }, [dispatch, trainingId]);
  
  if (!trainingId) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="text-center">
          <h2 className="text-lg font-semibold text-gray-900 mb-2">ID de entrenamiento requerido</h2>
          <p className="text-gray-600">No se pudo encontrar el ID del entrenamiento en la URL.</p>
        </div>
      </div>
    );
  }
  
  if (isLoading || !currentTraining) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }
  
  return <AttendanceTracker training={currentTraining} />;
};

export default AttendanceTrackerWrapper;