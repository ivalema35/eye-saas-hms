/// Implemented by a top-level module screen's State so the shell
/// ([HomeScreen]) can trigger a silent data reload when the app resumes
/// from the background — without needing to know each screen's internal
/// load-method name. Only the shell's directly-owned top-level tabs
/// implement this; screens reached via Navigator.push manage their own
/// lifecycle. See ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 4.
abstract class Refreshable {
  Future<void> refreshSilently();
}
