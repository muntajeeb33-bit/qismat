import 'package:flutter_test/flutter_test.dart';
import 'package:qismat/main.dart';

void main() {
  testWidgets('Qismat launches with Discover tab', (tester) async {
    await tester.pumpWidget(const QismatApp());
    expect(find.text('Qismat Connections'), findsOneWidget);
    expect(find.text('Discover'), findsWidgets);
  });
}
