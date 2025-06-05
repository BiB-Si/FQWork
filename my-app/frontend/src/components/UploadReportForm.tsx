import React, { useState } from 'react';
import {
  Upload,
  Button,
  Select,
  Typography,
  Spin,
  Alert,
  Row,
  Col,
  Space,
} from 'antd';
import {
  UploadOutlined,
  PrinterOutlined,
  FilePdfOutlined,
} from '@ant-design/icons';
import type { RcFile, UploadFile } from 'antd/es/upload/interface';
import axios from 'axios';
import jsPDF from 'jspdf';

const { Title, Paragraph } = Typography;
const { Option } = Select;

interface AnalysisResult {
  basic_data: {
    cpu_cores: number;
    ram_gb: number;
    gpu_vram_gb: number;
    ssd_type: string;
  };
  analysis: {
    problems: string[];
    recommendations: string[];
  };
}

const UploadReportForm: React.FC = () => {
  // Список загруженных файлов (AntD UploadFile)
  const [fileList, setFileList] = useState<UploadFile[]>([]);
  // Содержимое файла как текст
  const [fileContent, setFileContent] = useState<string>('');
  // Флаг, что файл прочитан и показан
  const [isPreviewed, setIsPreviewed] = useState(false);

  // Выбранная задача
  const [task, setTask] = useState<string>('modeling');

  // Результаты анализа
  const [result, setResult] = useState<AnalysisResult | null>(null);

  // Статусы
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Обработчик выбора файла из AntD Upload
  const handleChange = ({ fileList }: { fileList: UploadFile[] }) => {
    setFileList(fileList.slice(-1)); // оставляем только последний выбранный файл
    setFileContent('');
    setIsPreviewed(false);
    setResult(null);
    setError(null);
  };

  // Нажали "Просмотреть" — читаем содержимое файла
  const handlePreview = () => {
    setError(null);
    setResult(null);

    if (fileList.length === 0) {
      setError('Пожалуйста, выберите файл.');
      return;
    }

    const origin = fileList[0].originFileObj as RcFile | undefined;
    if (!origin) {
      setError('Не удалось получить исходный файл.');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const text = reader.result as string;
      setFileContent(text);
      setIsPreviewed(true);
    };
    reader.onerror = () => {
      setError('Ошибка при чтении файла.');
    };
    reader.readAsText(origin);
  };

  // Нажали "Анализ" — отправляем файл и task на сервер
  const handleAnalyze = async () => {
    setError(null);
    setResult(null);

    if (fileList.length === 0) {
      setError('Нет файла для анализа.');
      return;
    }

    const origin = fileList[0].originFileObj as RcFile | undefined;
    if (!origin) {
      setError('Не удалось получить исходный файл.');
      return;
    }

    const formData = new FormData();
    formData.append('report', origin);
    formData.append('build_id', '1');
    formData.append('task', task);

    setLoading(true);
    try {
      // axiosInstance.baseURL должен быть настроен на http://localhost:8000/api
      const resp = await axios.post<AnalysisResult>('/hwinfo/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          Authorization: `Bearer ${localStorage.getItem('accessToken')}`,
        },
      });
      setResult(resp.data);
    } catch (err: any) {
      console.error(err);
      const msg = err.response?.data?.error || err.message;
      setError('Ошибка при анализе: ' + msg);
    } finally {
      setLoading(false);
    }
  };

  // Открываем диалог печати браузера
  const handlePrint = () => {
    window.print();
  };

  // Создаёт PDF и предлагает сохранить
  const saveAsPDF = () => {
    if (!result) return;

    const doc = new jsPDF({
      unit: 'pt',
      format: 'a4',
    });

    const leftMargin = 40;
    let y = 40;

    doc.setFontSize(16);
    doc.text('Базовые характеристики', leftMargin, y);
    y += 24;

    doc.setFontSize(12);
    doc.text(`CPU: ${result.basic_data.cpu_cores} ядер`, leftMargin, y);
    y += 18;
    doc.text(`RAM: ${result.basic_data.ram_gb} ГБ`, leftMargin, y);
    y += 18;
    doc.text(
      `GPU VRAM: ${result.basic_data.gpu_vram_gb} ГБ`,
      leftMargin,
      y
    );
    y += 18;
    doc.text(`SSD: ${result.basic_data.ssd_type}`, leftMargin, y);
    y += 30;

    doc.setFontSize(16);
    doc.text('Найденные проблемы', leftMargin, y);
    y += 24;

    doc.setFontSize(12);
    if (result.analysis.problems.length > 0) {
      result.analysis.problems.forEach((p, index) => {
        const lines = doc.splitTextToSize(`${index + 1}. ${p}`, 500);
        doc.text(lines, leftMargin, y);
        y += lines.length * 14;
        if (y > 750) {
          doc.addPage();
          y = 40;
        }
      });
    } else {
      doc.text('Проблем не обнаружено.', leftMargin, y);
      y += 18;
    }
    y += 20;

    doc.setFontSize(16);
    doc.text('Рекомендации', leftMargin, y);
    y += 24;

    doc.setFontSize(12);
    result.analysis.recommendations.forEach((rec, index) => {
      const lines = doc.splitTextToSize(`${index + 1}. ${rec}`, 500);
      doc.text(lines, leftMargin, y);
      y += lines.length * 14;
      if (y > 750) {
        doc.addPage();
        y = 40;
      }
    });

    doc.save('analysis_results.pdf');
  };

  return (
    <div style={{ padding: 20 }}>
      <Title level={2}>Загрузка и анализ HWInfo-отчёта</Title>
      <Row gutter={16}>
        {/* Левый столбец: выбор и просмотр файла */}
        <Col span={12}>
          <Upload
            beforeUpload={() => false}
            onChange={handleChange}
            fileList={fileList}
            accept=".txt,.csv,.html,.xml"
            maxCount={1}
          >
            <Button icon={<UploadOutlined />}>Выбрать файл</Button>
          </Upload>
          <div style={{ marginTop: 12 }}>
            <Button type="primary" onClick={handlePreview}>
              Просмотреть
            </Button>
          </div>

          {isPreviewed && (
            <div
              style={{
                marginTop: 16,
                padding: 12,
                border: '1px solid #ddd',
                borderRadius: 4,
                maxHeight: '60vh',
                overflowY: 'auto',
                whiteSpace: 'pre-wrap',
                background: '#fafafa',
              }}
            >
              <pre>{fileContent}</pre>
            </div>
          )}
        </Col>

        {/* Правый столбец: выбор задачи, анализ и результаты */}
        <Col span={12}>
          <div style={{ marginBottom: 12 }}>
            <Paragraph>Выберите задачу для анализа:</Paragraph>
            <Select
              style={{ width: '100%' }}
              value={task}
              onChange={(value) => setTask(value)}
            >
              <Option value="modeling">
                Моделирование (Blender / SolidWorks)
              </Option>
              <Option value="1c">Работа с 1С</Option>
              <Option value="video_edit">Видеомонтаж</Option>
            </Select>
          </div>

          <Space style={{ marginBottom: 12 }}>
            <Button
              type="primary"
              onClick={handleAnalyze}
              disabled={!isPreviewed || loading}
            >
              Анализ
            </Button>
            {result && (
              <>
                <Button
                  icon={<PrinterOutlined />}
                  onClick={handlePrint}
                >
                  Печать результатов
                </Button>
                <Button icon={<FilePdfOutlined />} onClick={saveAsPDF}>
                  Сохранить в PDF
                </Button>
              </>
            )}
          </Space>

          {loading && (
            <div style={{ marginTop: 20 }}>
              <Spin tip="Идёт анализ..." />
            </div>
          )}

          {error && (
            <Alert
              type="error"
              message="Ошибка"
              description={error}
              style={{ marginTop: 20 }}
            />
          )}

          {result && (
            <div style={{ marginTop: 20 }}>
              <Title level={4}>Базовые характеристики</Title>
              <Paragraph>CPU: {result.basic_data.cpu_cores} ядер</Paragraph>
              <Paragraph>RAM: {result.basic_data.ram_gb} ГБ</Paragraph>
              <Paragraph>
                GPU VRAM: {result.basic_data.gpu_vram_gb} ГБ
              </Paragraph>
              <Paragraph>SSD: {result.basic_data.ssd_type}</Paragraph>

              <Title level={4} style={{ marginTop: 16 }}>
                Найденные проблемы
              </Title>
              {result.analysis.problems.length > 0 ? (
                result.analysis.problems.map((p, idx) => (
                  <Paragraph key={idx} style={{ color: 'red' }}>
                    – {p}
                  </Paragraph>
                ))
              ) : (
                <Paragraph>Проблем не обнаружено.</Paragraph>
              )}

              <Title level={4} style={{ marginTop: 16 }}>
                Рекомендации
              </Title>
              {result.analysis.recommendations.map((rec, idx) => (
                <Paragraph key={idx}>– {rec}</Paragraph>
              ))}
            </div>
          )}
        </Col>
      </Row>
    </div>
  );
};

export default UploadReportForm;
