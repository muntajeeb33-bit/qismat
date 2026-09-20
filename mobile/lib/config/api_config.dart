abstract final class ApiConfig {
  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://admin.qismatconnections.com/api/v1',
  );
}
