// Throwaway — renders AppErrorState in both its forms for a visual check.
import 'dart:io';
import 'dart:ui' show ImageByteFormat;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eye_care_app/widgets/app_error_state.dart';

const _fonts = 'C:/src/bin/cache/artifacts/material_fonts';

Future<void> _loadFonts() async {
  Future<ByteData> bytes(String f) async => ByteData.view(File('$_fonts/$f').readAsBytesSync().buffer);
  await (FontLoader('Roboto')
        ..addFont(bytes('roboto-regular.ttf'))
        ..addFont(bytes('roboto-bold.ttf'))
        ..addFont(bytes('roboto-black.ttf')))
      .load();
  await (FontLoader('MaterialIcons')..addFont(bytes('materialicons-regular.otf'))).load();
}

Future<void> _shot(WidgetTester tester, Widget body, String path) async {
  tester.view.physicalSize = const Size(400, 800);
  tester.view.devicePixelRatio = 1.0;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  final key = GlobalKey();
  await tester.pumpWidget(MaterialApp(
    debugShowCheckedModeBanner: false,
    theme: ThemeData(fontFamily: 'Roboto'),
    home: RepaintBoundary(key: key, child: Scaffold(appBar: AppBar(title: const Text('Medicines')), body: body)),
  ));
  for (var i = 0; i < 9; i++) {
    await tester.pump(const Duration(milliseconds: 160));
  }
  final rb = key.currentContext!.findRenderObject() as RenderRepaintBoundary;
  final image = await rb.toImage(pixelRatio: 2.0);
  File(path).writeAsBytesSync((await image.toByteData(format: ImageByteFormat.png))!.buffer.asUint8List());
}

void main() {
  setUpAll(_loadFonts);
  testWidgets('compact in list', (t) => _shot(
        t,
        ListView(children: [
          const Padding(padding: EdgeInsets.all(16), child: TextField(decoration: InputDecoration(hintText: 'Search medicines'))),
          AppErrorState(message: 'SocketException: Failed host lookup', onRetry: () {}),
        ]),
        'shot_compact.png',
      ));
  testWidgets('full via AppErrorState', (t) => _shot(t, AppErrorState(message: 'Server error. Please try again later.', onRetry: () {}), 'shot_full.png'));
}
