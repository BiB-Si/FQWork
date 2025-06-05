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
    // При желании можно добавить: { key: 'gaming', label: 'Игры' }, ...
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
    // При первой загрузке страницы можно не сразу вызывать, ждём, пока пользователь выберет задачу
  }, []);

  if (!user) {
    return <Alert type="warning" message="Пожалуйста, войдите в систему." />;
  }

  return (
    <div style={{ maxWidth: 800, margin: '0 auto', padding: 20 }}>
      <Title level={2}>Рекомендации по апгрейду для отчёта #{reportId}</Title>
      <Paragraph>Выберите задачу (тип нагрузки), чтобы получить советы:</Paragraph>

      <Select
        style={{ width: 300, marginRight: 16 }}
        defaultValue={task}
        onChange={value => setTask(value)}
      >
        {tasksList.map(t => (
          <Option key={t.key} value={t.key}>
            {t.label}
          </Option>
        ))}
      </Select>
      <Button type="primary" onClick={fetchRecommendations}>
        Получить рекомендации
      </Button>

      <div style={{ marginTop: 24 }}>
        {loading && <Spin tip="Загружаем рекомендации..." />}
        {error && (
          <Alert
            type="error"
            message="Ошибка"
            description={error}
            style={{ marginTop: 16 }}
          />
        )}
        {data && (
          <Card style={{ marginTop: 16 }}>
            {data.problems.length > 0 && (
              <>
                <Title level={4}>Найденные проблемы:</Title>
                {data.problems.map((p, idx) => (
                  <Paragraph key={idx} style={{ color: 'red' }}>
                    – {p}
                  </Paragraph>
                ))}
              </>
            )}
            <Title level={4} style={{ marginTop: 16 }}>
              Рекомендации:
            </Title>
            {data.recommendations.map((rec, idx) => (
              <Paragraph key={idx}>– {rec}</Paragraph>
            ))}
          </Card>
        )}
      </div>
    </div>
  );
};

export default RecommendationPage;
