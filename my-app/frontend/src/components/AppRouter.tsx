// frontend/src/components/AppRouter.tsx
import React from 'react';
import { Routes, Route } from 'react-router-dom';
import { publicRoutes, authUserRoutes, RouteNames } from '../router';
import NotFound from '../pages/NotFound';
import PrivateRoute from './PrivateRoute';
import MainLayout from '../layouts/MainLayout';
import UploadReportForm from './UploadReportForm';
import RecommendationPage from '../pages/RecommendationPage';

const AppRouter: React.FC = () => (
  <Routes>
    {/* Публичные маршруты */}
    {publicRoutes.map(r => (
      <Route
        key={r.uri}
        path={r.uri}
        element={<r.component />}
      />
    ))}

    {/* Защищённые маршруты (внутри общего макета) */}
    {authUserRoutes.map(r => (
      <Route
        key={r.uri}
        path={r.uri}
        element={
          <PrivateRoute>
            
              <r.component />
            
          </PrivateRoute>
        }
      />
    ))}

    {/* Специальный маршрут «UploadReportForm», тоже внутри MainLayout */}
    <Route
      path="/upload-report"
      element={
        <PrivateRoute>
          <MainLayout>
            <UploadReportForm />
          </MainLayout>
        </PrivateRoute>
      }
    />

    {/* Маршрут для страницы рекомендаций (если нужен такой) */}
    <Route
      path="/recommendations/:reportId"
      element={
        <PrivateRoute>
          <MainLayout>
            <RecommendationPage />
          </MainLayout>
        </PrivateRoute>
      }
    />

    {/* 404 */}
    <Route path="*" element={<NotFound />} />
  </Routes>
);

export default AppRouter;
