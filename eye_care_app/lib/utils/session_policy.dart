abstract class SessionPolicy {
  static const offlineGrace = Duration(hours: 24);

  static bool canUseOfflineCache({
    required DateTime? lastVerifiedAt,
    required DateTime now,
    Duration grace = offlineGrace,
  }) {
    if (lastVerifiedAt == null || lastVerifiedAt.isAfter(now)) return false;
    return now.difference(lastVerifiedAt) <= grace;
  }
}
