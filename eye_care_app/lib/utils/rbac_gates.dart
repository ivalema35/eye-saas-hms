import '../services/permission_service.dart';

abstract class RbacGates {
  static const _categories = {'doctor', 'reception', 'ot_staff'};

  /// Must resolve to the same category `UserApiController::roleCategory`
  /// authorizes against, otherwise the app shows actions the API rejects
  /// (or hides ones it allows). [userCategory] is the API's own answer
  /// (`user_category`); the slug/name fallback mirrors the backend for
  /// older API builds. [isDoctorRole] only drives doctor form fields, not
  /// the permission family.
  static String userCategory({
    String? userCategory,
    required String slug,
    required String name,
  }) {
    if (userCategory != null && _categories.contains(userCategory)) {
      return userCategory;
    }
    final s = slug.toLowerCase();
    final n = name.toLowerCase();
    if (s.contains('doctor') || n.contains('doctor')) return 'doctor';
    if (s.contains('reception') || n.contains('reception')) return 'reception';
    return 'ot_staff';
  }

  static String userPermission({
    String? userCategory,
    required String slug,
    required String name,
    required String verb,
  }) =>
      'user_${RbacGates.userCategory(userCategory: userCategory, slug: slug, name: name)}_$verb';

  static bool canManageUserRole({
    String? userCategory,
    required String slug,
    required String name,
    required String verb,
    PermissionService? permissions,
  }) =>
      (permissions ?? PermissionService.instance).can(userPermission(
        userCategory: userCategory,
        slug: slug,
        name: name,
        verb: verb,
      ));
}
