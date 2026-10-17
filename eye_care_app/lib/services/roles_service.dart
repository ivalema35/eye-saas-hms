import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/role_models.dart';
import 'base_service.dart';

class RolesService with AuthenticatedService {
  RolesService._();
  static final RolesService instance = RolesService._();


  Future<List<RoleModel>> fetchRoles() async {
    final resp = await http
        .get(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles'),
          headers: await headers,
        )
        .timeout(AppConfig.requestTimeout);

    final body = parseApiResponse(resp);
    final data = body['data'] as List? ?? [];
    return data
        .map((e) => RoleModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<RoleModel> fetchRole(int id) async {
    final resp = await http
        .get(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles/$id'),
          headers: await headers,
        )
        .timeout(AppConfig.requestTimeout);

    final body = parseApiResponse(resp);
    return RoleModel.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<AllPermissionsResult> fetchAllPermissions() async {
    final resp = await http
        .get(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles/permissions'),
          headers: await headers,
        )
        .timeout(AppConfig.requestTimeout);

    return AllPermissionsResult.fromJson(parseApiResponse(resp));
  }

  Future<void> createRole({
    required String name,
    String? color,
    String? description,
    required List<int> permissionIds,
  }) async {
    final resp = await http
        .post(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles'),
          headers: await headers,
          body: jsonEncode({
            'name': name,
            if (color != null) 'color': color,
            if (description != null) 'description': description,
            'permission_ids': permissionIds,
          }),
        )
        .timeout(AppConfig.requestTimeout);

    parseApiResponse(resp);
  }

  Future<void> updateRole({
    required int id,
    String? name,
    String? color,
    String? description,
    List<int>? permissionIds,
  }) async {
    final resp = await http
        .put(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles/$id'),
          headers: await headers,
          body: jsonEncode(<String, dynamic>{
            'name': ?name,
            'color': ?color,
            'description': ?description,
            'permission_ids': ?permissionIds,
          }),
        )
        .timeout(AppConfig.requestTimeout);

    parseApiResponse(resp);
  }

  Future<void> deleteRole(int id) async {
    final resp = await http
        .delete(
          Uri.parse('${AppConfig.hospitalApiUrl}/config/roles/$id'),
          headers: await headers,
        )
        .timeout(AppConfig.requestTimeout);

    parseApiResponse(resp);
  }
}
