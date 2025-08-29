import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchAttendances, fetchCategories, fetchPlayers } from '../../../store/sportsSlice';
import { AttendanceFilters } from '../../../types/sports';
import AttendanceStats from './AttendanceStats';

interface AttendanceReportProps {
  onExport?: (data: any) => void;
}

const AttendanceReport: React.FC<AttendanceReportProps> = ({ onExport }) => {
  const dispatch = useAppDispatch();
  const { attendances, categories, players, isLoading } = useAppSelector((state: any) => ({
    attendances: state.sports.attendances,
    categories: state.sports.categories,
    players: state.sports.players,
    isLoading: state.sports.isLoading
  }));

  const [filters, setFilters] = useState<AttendanceFilters>({
    page: 1,
    per_page: 1000
  });

  const [reportType, setReportType] = useState<'summary' | 'detailed' | 'player' | 'category'>('summary');
  const [selectedPlayer, setSelectedPlayer] = useState<string>('');
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [dateRange, setDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days ago
    end: new Date().toISOString().split('T')[0] // today
  });

  useEffect(() => {
    dispatch(fetchCategories({ page: 1, per_page: 100 }));
    dispatch(fetchPlayers({ page: 1, per_page: 100 }));
  }, [dispatch]);

  useEffect(() => {
    const reportFilters: AttendanceFilters = {
      ...filters,
      date_from: dateRange.start,
      date_to: dateRange.end
    };

    if (selectedPlayer && reportType === 'player') {
      reportFilters.player_id = selectedPlayer;
    }

    if (selectedCategory && reportType === 'category') {
      reportFilters.category_id = selectedCategory;
    }

    dispatch(fetchAttendances(reportFilters));
  }, [dispatch, filters, dateRange, selectedPlayer, selectedCategory, reportType]);

  const generateReportData = () => {
    const reportData = {
      metadata: {
        reportType,
        dateRange,
        selectedPlayer: selectedPlayer ? players.find((p: any) => p.id === selectedPlayer)?.full_name : null,
        selectedCategory: selectedCategory ? categories.find((c: any) => c.id === selectedCategory)?.name : null,
        generatedAt: new Date().toISOString(),
        totalRecords: attendances.length
      },
      summary: calculateSummaryStats(),
      data: attendances
    };

    switch (reportType) {
      case 'player':
        reportData.data = generatePlayerReport();
        break;
      case 'category':
        reportData.data = generateCategoryReport();
        break;
      case 'detailed':
        reportData.data = generateDetailedReport();
        break;
      default:
        reportData.data = generateSummaryReport();
    }

    return reportData;
  };

  const calculateSummaryStats = () => {
    const stats = {
      totalTrainings: attendances.length,
      totalPlayers: new Set(attendances.map((a: any) => a.player_id)).size,
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
      pending: 0,
      attendanceRate: 0,
      punctualityRate: 0,
      averageLateMinutes: 0
    };

    let totalLateMinutes = 0;
    let lateCount = 0;

    attendances.forEach((attendance: any) => {
      stats[attendance.status as keyof typeof stats]++;
      
      if (attendance.status === 'late' && attendance.late_minutes) {
        totalLateMinutes += attendance.late_minutes;
        lateCount++;
      }
    });

    if (stats.totalTrainings > 0) {
      stats.attendanceRate = Math.round(((stats.present + stats.late) / stats.totalTrainings) * 100);
      stats.punctualityRate = Math.round((stats.present / (stats.present + stats.late)) * 100);
    }

    if (lateCount > 0) {
      stats.averageLateMinutes = Math.round(totalLateMinutes / lateCount);
    }

    return stats;
  };

  const generateSummaryReport = () => {
    const monthlyStats: { [key: string]: any } = {};
    
    attendances.forEach((attendance: any) => {
      const date = new Date(attendance.training?.date || attendance.created_at);
      const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      
      if (!monthlyStats[monthKey]) {
        monthlyStats[monthKey] = {
          month: monthKey,
          present: 0,
          absent: 0,
          late: 0,
          excused: 0,
          pending: 0,
          total: 0
        };
      }
      
      monthlyStats[monthKey][attendance.status]++;
      monthlyStats[monthKey].total++;
    });

    return Object.values(monthlyStats).map((month: any) => ({
      ...month,
      attendanceRate: month.total > 0 ? Math.round(((month.present + month.late) / month.total) * 100) : 0
    }));
  };

  const generatePlayerReport = () => {
    const playerStats: { [key: string]: any } = {};
    
    attendances.forEach((attendance: any) => {
      const playerId = attendance.player_id;
      const player = attendance.player;
      
      if (!playerStats[playerId]) {
        playerStats[playerId] = {
          playerId,
          playerName: player?.full_name || 'Jugador desconocido',
          jerseyNumber: player?.jersey_number,
          position: player?.position,
          present: 0,
          absent: 0,
          late: 0,
          excused: 0,
          pending: 0,
          total: 0,
          totalLateMinutes: 0,
          lateCount: 0
        };
      }
      
      playerStats[playerId][attendance.status]++;
      playerStats[playerId].total++;
      
      if (attendance.status === 'late' && attendance.late_minutes) {
        playerStats[playerId].totalLateMinutes += attendance.late_minutes;
        playerStats[playerId].lateCount++;
      }
    });

    return Object.values(playerStats).map((player: any) => ({
      ...player,
      attendanceRate: player.total > 0 ? Math.round(((player.present + player.late) / player.total) * 100) : 0,
      punctualityRate: (player.present + player.late) > 0 ? Math.round((player.present / (player.present + player.late)) * 100) : 0,
      averageLateMinutes: player.lateCount > 0 ? Math.round(player.totalLateMinutes / player.lateCount) : 0
    })).sort((a, b) => b.attendanceRate - a.attendanceRate);
  };

  const generateCategoryReport = () => {
    const categoryStats: { [key: string]: any } = {};
    
    attendances.forEach((attendance: any) => {
      const categoryId = attendance.training?.category_id;
      const category = categories.find((c: any) => c.id === categoryId);
      
      if (!categoryStats[categoryId]) {
        categoryStats[categoryId] = {
          categoryId,
          categoryName: category?.name || 'Categoría desconocida',
          present: 0,
          absent: 0,
          late: 0,
          excused: 0,
          pending: 0,
          total: 0,
          uniquePlayers: new Set()
        };
      }
      
      categoryStats[categoryId][attendance.status]++;
      categoryStats[categoryId].total++;
      categoryStats[categoryId].uniquePlayers.add(attendance.player_id);
    });

    return Object.values(categoryStats).map((category: any) => ({
      ...category,
      uniquePlayers: category.uniquePlayers.size,
      attendanceRate: category.total > 0 ? Math.round(((category.present + category.late) / category.total) * 100) : 0
    })).sort((a, b) => b.attendanceRate - a.attendanceRate);
  };

  const generateDetailedReport = () => {
    return attendances.map((attendance: any) => ({
      id: attendance.id,
      date: attendance.training?.date,
      trainingType: attendance.training?.type,
      trainingLocation: attendance.training?.location,
      playerName: attendance.player?.full_name,
      jerseyNumber: attendance.player?.jersey_number,
      position: attendance.player?.position,
      categoryName: categories.find((c: any) => c.id === attendance.training?.category_id)?.name,
      status: attendance.status,
      arrivalTime: attendance.arrival_time,
      lateMinutes: attendance.late_minutes,
      notes: attendance.notes,
      createdAt: attendance.created_at,
      updatedAt: attendance.updated_at
    })).sort((a: any, b: any) => new Date(b.date).getTime() - new Date(a.date).getTime());
  };

  const exportToCSV = () => {
    const reportData = generateReportData();
    
    let csvContent = '';
    let headers: string[] = [];
    let rows: any[] = [];

    switch (reportType) {
      case 'player':
        headers = ['Jugador', 'Número', 'Posición', 'Total', 'Presentes', 'Tardanzas', 'Ausentes', 'Justificados', 'Asistencia %', 'Puntualidad %', 'Tardanza Promedio (min)'];
        rows = reportData.data.map((player: any) => [
          player.playerName,
          player.jerseyNumber || '',
          player.position || '',
          player.total,
          player.present,
          player.late,
          player.absent,
          player.excused,
          player.attendanceRate,
          player.punctualityRate,
          player.averageLateMinutes
        ]);
        break;
      case 'category':
        headers = ['Categoría', 'Total', 'Jugadores', 'Presentes', 'Tardanzas', 'Ausentes', 'Justificados', 'Asistencia %'];
        rows = reportData.data.map((category: any) => [
          category.categoryName,
          category.total,
          category.uniquePlayers,
          category.present,
          category.late,
          category.absent,
          category.excused,
          category.attendanceRate
        ]);
        break;
      case 'detailed':
        headers = ['Fecha', 'Jugador', 'Número', 'Categoría', 'Tipo Entrenamiento', 'Ubicación', 'Estado', 'Hora Llegada', 'Tardanza (min)', 'Notas'];
        rows = reportData.data.map((record: any) => [
          record.date,
          record.playerName,
          record.jerseyNumber || '',
          record.categoryName || '',
          record.trainingType || '',
          record.trainingLocation || '',
          record.status,
          record.arrivalTime || '',
          record.lateMinutes || '',
          record.notes || ''
        ]);
        break;
      default:
        headers = ['Mes', 'Total', 'Presentes', 'Tardanzas', 'Ausentes', 'Justificados', 'Asistencia %'];
        rows = reportData.data.map((month: any) => [
          month.month,
          month.total,
          month.present,
          month.late,
          month.absent,
          month.excused,
          month.attendanceRate
        ]);
    }

    csvContent = headers.join(',') + '\n';
    csvContent += rows.map(row => row.map((cell: any) => `"${cell}"`).join(',')).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `reporte_asistencia_${reportType}_${dateRange.start}_${dateRange.end}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    if (onExport) {
      onExport(reportData);
    }
  };

  const exportToPDF = () => {
    // This would typically use a library like jsPDF
    // For now, we'll just trigger the onExport callback
    const reportData = generateReportData();
    
    if (onExport) {
      onExport({ ...reportData, format: 'pdf' });
    } else {
      alert('Funcionalidad de exportar a PDF no implementada. Use CSV por ahora.');
    }
  };

  const getReportTypeLabel = (type: string): string => {
    switch (type) {
      case 'summary': return 'Resumen General';
      case 'detailed': return 'Reporte Detallado';
      case 'player': return 'Por Jugador';
      case 'category': return 'Por Categoría';
      default: return type;
    }
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Report Configuration */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">
          Generador de Reportes de Asistencia
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tipo de Reporte
            </label>
            <select
              value={reportType}
              onChange={(e) => setReportType(e.target.value as any)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="summary">Resumen General</option>
              <option value="detailed">Reporte Detallado</option>
              <option value="player">Por Jugador</option>
              <option value="category">Por Categoría</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Fecha Inicio
            </label>
            <input
              type="date"
              value={dateRange.start}
              onChange={(e) => setDateRange(prev => ({ ...prev, start: e.target.value }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Fecha Fin
            </label>
            <input
              type="date"
              value={dateRange.end}
              onChange={(e) => setDateRange(prev => ({ ...prev, end: e.target.value }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div className="flex items-end space-x-2">
            <button
              onClick={exportToCSV}
              className="flex-1 px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              Exportar CSV
            </button>
            <button
              onClick={exportToPDF}
              className="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
              Exportar PDF
            </button>
          </div>
        </div>
        
        {/* Additional Filters */}
        {reportType === 'player' && (
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Seleccionar Jugador (opcional)
            </label>
            <select
              value={selectedPlayer}
              onChange={(e) => setSelectedPlayer(e.target.value)}
              className="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos los jugadores</option>
              {players.map((player: any) => (
                <option key={player.id} value={player.id}>
                  {player.full_name} {player.jersey_number ? `(#${player.jersey_number})` : ''}
                </option>
              ))}
            </select>
          </div>
        )}
        
        {reportType === 'category' && (
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Seleccionar Categoría (opcional)
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todas las categorías</option>
              {categories.map((category: any) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
        )}
        
        <div className="text-sm text-gray-600">
          <strong>Reporte:</strong> {getReportTypeLabel(reportType)} | 
          <strong>Período:</strong> {new Date(dateRange.start).toLocaleDateString('es-ES')} - {new Date(dateRange.end).toLocaleDateString('es-ES')} | 
          <strong>Registros:</strong> {attendances.length}
        </div>
      </div>

      {/* Statistics Display */}
      <AttendanceStats 
        playerId={reportType === 'player' ? selectedPlayer : undefined}
        categoryId={reportType === 'category' ? selectedCategory : undefined}
        dateRange={dateRange}
      />

      {/* Report Preview */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          Vista Previa del Reporte - {getReportTypeLabel(reportType)}
        </h3>
        
        <div className="overflow-x-auto">
          {reportType === 'summary' && (
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mes</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presentes</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tardanzas</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ausentes</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asistencia</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {generateSummaryReport().slice(0, 10).map((month: any, index) => (
                  <tr key={index}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {new Date(month.month + '-01').toLocaleDateString('es-ES', { year: 'numeric', month: 'long' })}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{month.total}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">{month.present}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">{month.late}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">{month.absent}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{month.attendanceRate}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          
          {reportType === 'player' && (
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jugador</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presentes</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tardanzas</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ausentes</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asistencia</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {generatePlayerReport().slice(0, 10).map((player: any, index) => (
                  <tr key={index}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{player.playerName}</div>
                      <div className="text-sm text-gray-500">
                        {player.jerseyNumber && `#${player.jerseyNumber}`} {player.position}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{player.total}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">{player.present}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">{player.late}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">{player.absent}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{player.attendanceRate}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          
          {generateReportData().data.length > 10 && (
            <div className="mt-4 text-sm text-gray-500 text-center">
              Mostrando los primeros 10 registros. Exporte para ver todos los datos.
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AttendanceReport;