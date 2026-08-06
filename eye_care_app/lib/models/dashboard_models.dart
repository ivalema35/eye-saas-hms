class DashboardData {
  final int? subscriptionDaysLeft;
  final bool isDoctor;
  final bool isOtDoctor;
  final bool isReceptionist;
  final int myTodayPatients;
  final int myPrimaryPending;
  final int mySecondaryPending;
  final int todayPatients;
  final int primaryQueueCount;
  final int secondaryQueueCount;
  final int todayWalkin;
  final int todayPhone;
  final int todayRegistrations;
  final double revenueToday;
  final double revenueMonth;
  final double revenueYear;
  final int otToday;
  final int otOperated;
  final int otPending;
  final int totalStaff;
  final List<QueuePatient> primaryQueue;
  final List<ReceptionistStat> receptionists;
  final WaitThresholds waitThresholds;
  final ReceptionistStats? receptionistStats;
  final List<DoctorCard> doctorCards;
  final int? pendingShareRequestsCount;

  const DashboardData({
    this.subscriptionDaysLeft,
    this.isDoctor = false,
    this.isOtDoctor = false,
    this.isReceptionist = false,
    this.myTodayPatients = 0,
    this.myPrimaryPending = 0,
    this.mySecondaryPending = 0,
    required this.todayPatients,
    required this.primaryQueueCount,
    required this.secondaryQueueCount,
    required this.todayWalkin,
    required this.todayPhone,
    required this.todayRegistrations,
    required this.revenueToday,
    required this.revenueMonth,
    required this.revenueYear,
    required this.otToday,
    required this.otOperated,
    required this.otPending,
    required this.totalStaff,
    required this.primaryQueue,
    required this.receptionists,
    required this.waitThresholds,
    this.receptionistStats,
    this.doctorCards = const [],
    this.pendingShareRequestsCount,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return DashboardData(
      subscriptionDaysLeft: data['subscription_days_left'] as int?,
      isDoctor: data['is_doctor'] as bool? ?? false,
      isOtDoctor: data['is_ot_doctor'] as bool? ?? false,
      isReceptionist: data['is_receptionist'] as bool? ?? false,
      myTodayPatients: data['my_today_patients'] as int? ?? 0,
      myPrimaryPending: data['my_primary_pending'] as int? ?? 0,
      mySecondaryPending: data['my_secondary_pending'] as int? ?? 0,
      todayPatients: data['today_patients'] as int? ?? 0,
      primaryQueueCount: data['primary_queue_count'] as int? ?? 0,
      secondaryQueueCount: data['secondary_queue_count'] as int? ?? 0,
      todayWalkin: data['today_walkin'] as int? ?? 0,
      todayPhone: data['today_phone'] as int? ?? 0,
      todayRegistrations: data['today_registrations'] as int? ?? 0,
      revenueToday: (data['revenue_today'] as num? ?? 0).toDouble(),
      revenueMonth: (data['revenue_month'] as num? ?? 0).toDouble(),
      revenueYear: (data['revenue_year'] as num? ?? 0).toDouble(),
      otToday: data['ot_today'] as int? ?? 0,
      otOperated: data['ot_operated'] as int? ?? 0,
      otPending: data['ot_pending'] as int? ?? 0,
      totalStaff: data['total_staff'] as int? ?? 0,
      primaryQueue: (data['primary_queue'] as List<dynamic>? ?? [])
          .map((e) => QueuePatient.fromJson(e as Map<String, dynamic>))
          .toList(),
      receptionists: (data['receptionists'] as List<dynamic>? ?? [])
          .map((e) => ReceptionistStat.fromJson(e as Map<String, dynamic>))
          .toList(),
      waitThresholds: WaitThresholds.fromJson(
        data['wait_thresholds'] as Map<String, dynamic>? ?? {},
      ),
      receptionistStats: data['receptionist_stats'] != null
          ? ReceptionistStats.fromJson(data['receptionist_stats'] as Map<String, dynamic>)
          : null,
      doctorCards: (data['doctor_cards'] as List<dynamic>? ?? [])
          .map((e) => DoctorCard.fromJson(e as Map<String, dynamic>))
          .toList(),
      pendingShareRequestsCount: data['pending_share_requests_count'] as int?,
    );
  }
}

class QueuePatient {
  final int id;
  final String patientCode;
  final String fullName;
  final int? age;
  final String? gender;
  final int? doctorPatientNo;
  final DateTime? checkedInAt;
  final DateTime? registeredAt;
  final String? doctorName;
  final String? doctorPrefix;
  final bool hasHistory;

  const QueuePatient({
    required this.id,
    required this.patientCode,
    required this.fullName,
    this.age,
    this.gender,
    this.doctorPatientNo,
    this.checkedInAt,
    this.registeredAt,
    this.doctorName,
    this.doctorPrefix,
    this.hasHistory = false,
  });

  factory QueuePatient.fromJson(Map<String, dynamic> json) {
    return QueuePatient(
      id: json['id'] as int,
      patientCode: json['patient_code'] as String? ?? '',
      fullName: json['full_name'] as String? ?? '',
      age: json['age'] as int?,
      gender: json['gender'] as String?,
      doctorPatientNo: json['doctor_patient_no'] as int?,
      checkedInAt: json['checked_in_at'] != null
          ? DateTime.tryParse(json['checked_in_at'] as String)
          : null,
      registeredAt: json['registered_at'] != null
          ? DateTime.tryParse(json['registered_at'] as String)
          : null,
      doctorName: json['doctor_name'] as String?,
      doctorPrefix: json['doctor_prefix'] as String?,
      hasHistory: json['has_history'] as bool? ?? false,
    );
  }

  int waitMinutes() {
    final ref = checkedInAt ?? registeredAt;
    if (ref == null) return 0;
    return DateTime.now().difference(ref).inMinutes.clamp(0, 9999);
  }
}

class ReceptionistStat {
  final int id;
  final String name;
  final int todayCount;
  final double todayGross;
  final double todayFoc;
  final double todayNet;

  const ReceptionistStat({
    required this.id,
    required this.name,
    required this.todayCount,
    required this.todayGross,
    required this.todayFoc,
    required this.todayNet,
  });

  factory ReceptionistStat.fromJson(Map<String, dynamic> json) {
    return ReceptionistStat(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      todayCount: json['today_count'] as int? ?? 0,
      todayGross: (json['today_gross'] as num? ?? 0).toDouble(),
      todayFoc: (json['today_foc'] as num? ?? 0).toDouble(),
      todayNet: (json['today_net'] as num? ?? 0).toDouble(),
    );
  }
}

class ReceptionistStats {
  final int myPatientsToday;
  final int myTotalPatients;
  final int myWalkin;
  final int myPhone;
  final double todayCollection;
  final int pendingPhoneCheckin;

  const ReceptionistStats({
    required this.myPatientsToday,
    required this.myTotalPatients,
    required this.myWalkin,
    required this.myPhone,
    required this.todayCollection,
    required this.pendingPhoneCheckin,
  });

  factory ReceptionistStats.fromJson(Map<String, dynamic> json) {
    return ReceptionistStats(
      myPatientsToday: json['my_patients_today'] as int? ?? 0,
      myTotalPatients: json['my_total_patients'] as int? ?? 0,
      myWalkin: json['my_walkin'] as int? ?? 0,
      myPhone: json['my_phone'] as int? ?? 0,
      todayCollection: (json['today_collection'] as num? ?? 0).toDouble(),
      pendingPhoneCheckin: json['pending_phone_checkin'] as int? ?? 0,
    );
  }
}

class DoctorCard {
  final int id;
  final String name;
  final String? doctorType;
  final String? roleSlug;
  final int assignedToday;
  final int primaryCount;
  final int secondaryCount;

  const DoctorCard({
    required this.id,
    required this.name,
    this.doctorType,
    this.roleSlug,
    required this.assignedToday,
    required this.primaryCount,
    required this.secondaryCount,
  });

  factory DoctorCard.fromJson(Map<String, dynamic> json) {
    return DoctorCard(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      doctorType: json['doctor_type'] as String?,
      roleSlug: json['role_slug'] as String?,
      assignedToday: json['assigned_today'] as int? ?? 0,
      primaryCount: json['primary_count'] as int? ?? 0,
      secondaryCount: json['secondary_count'] as int? ?? 0,
    );
  }
}

class WaitThresholds {
  final int rGreen;
  final int rOrange;
  final int rRed;

  const WaitThresholds({
    this.rGreen = 30,
    this.rOrange = 60,
    this.rRed = 120,
  });

  factory WaitThresholds.fromJson(Map<String, dynamic> json) {
    return WaitThresholds(
      rGreen: json['r_green'] as int? ?? 30,
      rOrange: json['r_orange'] as int? ?? 60,
      rRed: json['r_red'] as int? ?? 120,
    );
  }
}
