import React, { useEffect, useState, useContext } from 'react';
import { useParams } from 'react-router-dom';
import { axiosInstance } from '../config/axios';
import { UserContext } from '../App';
import { Card, Select, Button, Typography, Spin, Alert } from 'antd';

const { Option } = Select;
const { Title, Paragraph } = Typography;

interface RecommendationResponse {
  problems: string[];
  recommendations: string[];
  cpu_score?: number;
  gpu_score?: number;
  cpu_cores?: number;
  ram_gb?: number;
  gpu_vram_gb?: number;
  ssd_type?: string;
}

const RecommendationPage: React.FC = () => {
  const { reportId } = useParams<{ reportId: string }>();
  const user = useContext(UserContext);
  const [task, setTask] = useState<string>('modeling');
  const [data, setData] = useState<RecommendationResponse | null>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const tasksList = [
    { key: 'modeling', label: 'Моделирование (Blender/SolidWorks)' },
    { key: '1c', label: 'Работа с 1С' },
    { key: 'video_edit', label: 'Видеомонтаж' },
  ];

  const fetchRecommendations = async () => {
    if (!reportId) return;
    setLoading(true);
    setError(null);
    try {
      const resp = await axiosInstance.get<RecommendationResponse>(
        `/hwinfo/reports/${reportId}/recommendations/${task}`
      );
      setData(resp.data);
    } catch (e: any) {
      console.error(e);
      setError(
        e.response?.data?.error ||
        'Ошибка при получении рекомендаций. Попробуйте позже.'
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // можно автоматически вызывать при загрузке:
    fetchRecommendations();
  }, [task]);

  if (!user) {
    return <Alert type="warning" message="Пожалуйста, войдите в систему." />;
  }

  return (
    <div style={{ maxWidth: 800, margin: '0 auto', padding: 20 }}>
      <Title level={2}>Рекомендации по апгрейду для отчёта #{reportId}</Title>

      <Paragraph>Выберите задачу для анализа:</Paragraph>
      <Select
        value={task}
        onChange={setTask}
        style={{ width: '100%', marginBottom: 20 }}
      >
        {tasksList.map((t) => (
          <Option key={t.key} value={t.key}>
            {t.label}
          </Option>
        ))}
      </Select>

      <Button type="primary" onClick={fetchRecommendations} loading={loading}>
        Анализ
      </Button>

      {loading && <Spin style={{ marginTop: 20 }} />}

      {error && <Alert type="error" message={error} style={{ marginTop: 20 }} />}

      {data && (
        <>
          <Card style={{ marginTop: 20 }} title="Базовые характеристики">
            <p>CPU: {data.cpu_cores} ядер</p>
            <p>CPU Score: {data.cpu_score} баллов</p>
            <p>RAM: {data.ram_gb} ГБ</p>
            <p>GPU VRAM: {data.gpu_vram_gb} ГБ</p>
            <p>GPU Score: {data.gpu_score} баллов</p>
            <p>SSD: {data.ssd_type}</p>
          </Card>

          <Card style={{ marginTop: 20 }} title="Найденные проблемы">
            {data.problems.length > 0 ? (
              data.problems.map((p, i) => <p key={i} style={{ color: 'red' }}>{p}</p>)
            ) : (
              <p style={{ color: 'green' }}>Все параметры соответствуют требованиям.</p>
            )}
          </Card>

          <Card style={{ marginTop: 20 }} title="Рекомендации">
            {data.recommendations.map((r, i) => (
              <p key={i}>{r}</p>
            ))}
          </Card>
        </>
      )}
    </div>
  );
};

export default RecommendationPage;
