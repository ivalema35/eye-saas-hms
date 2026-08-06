import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../services/platform_auth_service.dart';
import 'platform_home_screen.dart';

class PlatformLoginScreen extends StatefulWidget {
  const PlatformLoginScreen({super.key});

  @override
  State<PlatformLoginScreen> createState() => _PlatformLoginScreenState();
}

class _PlatformLoginScreenState extends State<PlatformLoginScreen>
    with TickerProviderStateMixin {
  final _formKey     = GlobalKey<FormState>();
  final _emailCtrl   = TextEditingController();
  final _passwordCtrl = TextEditingController();

  bool _obscure    = true;
  bool _isLoading  = false;
  bool _btnPressed = false;
  String? _errorMessage;

  late final AnimationController _entryCtrl;
  late final AnimationController _shakeCtrl;

  late final Animation<double> _cardOpacity;
  late final Animation<Offset>  _cardSlide;
  late final Animation<double> _headerOpacity;
  late final Animation<Offset>  _headerSlide;
  late final Animation<double>  _shake;

  @override
  void initState() {
    super.initState();

    _entryCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 700));
    _shakeCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 480));

    _headerOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _entryCtrl, curve: const Interval(0.0, 0.5, curve: Curves.easeOut)),
    );
    _headerSlide = Tween<Offset>(begin: const Offset(0, -0.3), end: Offset.zero).animate(
      CurvedAnimation(parent: _entryCtrl, curve: const Interval(0.0, 0.55, curve: Curves.easeOut)),
    );
    _cardOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _entryCtrl, curve: const Interval(0.35, 0.9, curve: Curves.easeOut)),
    );
    _cardSlide = Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(
      CurvedAnimation(parent: _entryCtrl, curve: const Interval(0.35, 1.0, curve: Curves.easeOutQuart)),
    );

    _shake = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0.0,   end: -11.0), weight: 1),
      TweenSequenceItem(tween: Tween(begin: -11.0, end: 11.0),  weight: 2),
      TweenSequenceItem(tween: Tween(begin: 11.0,  end: -9.0),  weight: 2),
      TweenSequenceItem(tween: Tween(begin: -9.0,  end: 9.0),   weight: 2),
      TweenSequenceItem(tween: Tween(begin: 9.0,   end: -5.0),  weight: 2),
      TweenSequenceItem(tween: Tween(begin: -5.0,  end: 5.0),   weight: 2),
      TweenSequenceItem(tween: Tween(begin: 5.0,   end: 0.0),   weight: 1),
    ]).animate(CurvedAnimation(parent: _shakeCtrl, curve: Curves.linear));

    _entryCtrl.forward();
  }

  @override
  void dispose() {
    _entryCtrl.dispose();
    _shakeCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() { _isLoading = true; _errorMessage = null; });

    final result = await PlatformAuthService.instance.login(
      _emailCtrl.text.trim(),
      _passwordCtrl.text,
    );

    if (!mounted) return;

    if (result.success) {
      Navigator.of(context).pushReplacement(
        PageRouteBuilder(
          pageBuilder: (_, _, _) => PlatformHomeScreen(admin: result.admin!),
          transitionsBuilder: (_, animation, _, child) => FadeTransition(
            opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
            child: child,
          ),
          transitionDuration: const Duration(milliseconds: 350),
        ),
      );
    } else {
      setState(() { _isLoading = false; _errorMessage = result.message; });
      HapticFeedback.mediumImpact();
      _shakeCtrl.forward(from: 0);
    }
  }

  @override
  Widget build(BuildContext context) {
    final mq      = MediaQuery.of(context);
    final headerH = mq.size.height * 0.30;
    final topPad  = mq.padding.top;

    return Scaffold(
      backgroundColor: AppColors.background,
      resizeToAvoidBottomInset: true,
      body: Stack(
        children: [
          // Navy header
          Positioned(
            top: 0, left: 0, right: 0,
            child: Container(
              height: headerH,
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: const BorderRadius.only(
                  bottomLeft:  Radius.circular(40),
                  bottomRight: Radius.circular(40),
                ),
              ),
            ),
          ),

          SafeArea(
            bottom: false,
            child: SingleChildScrollView(
              physics: const ClampingScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Header
                  SizedBox(
                    height: headerH - topPad - 20,
                    child: Center(
                      child: FadeTransition(
                        opacity: _headerOpacity,
                        child: SlideTransition(
                          position: _headerSlide,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 64, height: 64,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.12),
                                  shape: BoxShape.circle,
                                  border: Border.all(color: Colors.white.withValues(alpha: 0.25), width: 1.5),
                                ),
                                child: const Icon(Icons.admin_panel_settings_rounded, color: Colors.white, size: 32),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'SUPER ADMIN',
                                style: GoogleFonts.poppins(
                                  fontSize: 18, fontWeight: FontWeight.w900, color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                'PLATFORM CONSOLE',
                                style: GoogleFonts.poppins(
                                  fontSize: 10, fontWeight: FontWeight.w700,
                                  color: Colors.white.withValues(alpha: 0.55), letterSpacing: 2.5,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),

                  // Card with shake
                  AnimatedBuilder(
                    animation: _shakeCtrl,
                    builder: (_, child) => Transform.translate(
                      offset: Offset(_shake.value, 0),
                      child: child,
                    ),
                    child: FadeTransition(
                      opacity: _cardOpacity,
                      child: SlideTransition(
                        position: _cardSlide,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          child: _buildCard(),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 24),

                  // Back to hospital login
                  TextButton.icon(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.arrow_back_rounded, size: 16, color: AppColors.textSecondary),
                    label: Text(
                      'Back to Hospital Staff Login',
                      style: GoogleFonts.poppins(
                        fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textSecondary,
                      ),
                    ),
                  ),

                  const SizedBox(height: 24),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(color: AppColors.primaryA12, blurRadius: 40, offset: const Offset(0, 16)),
        ],
      ),
      padding: const EdgeInsets.all(28),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Sign In', style: GoogleFonts.poppins(fontSize: 22, fontWeight: FontWeight.w900, color: AppColors.primary)),
            const SizedBox(height: 4),
            Text('Platform administrator access', style: GoogleFonts.poppins(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.primaryA40)),
            const SizedBox(height: 22),

            // Error banner
            AnimatedSize(
              duration: const Duration(milliseconds: 280),
              curve: Curves.easeOutQuart,
              child: _errorMessage != null
                  ? Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: _buildErrorBanner(_errorMessage!),
                    )
                  : const SizedBox.shrink(),
            ),

            _buildField(
              controller: _emailCtrl,
              hint: 'Admin Email',
              icon: Icons.mail_outline_rounded,
              keyboardType: TextInputType.emailAddress,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Please enter your email' : null,
            ),
            const SizedBox(height: 12),

            _buildField(
              controller: _passwordCtrl,
              hint: 'Password',
              icon: Icons.lock_outline_rounded,
              obscureText: _obscure,
              suffixIcon: GestureDetector(
                onTap: () => setState(() => _obscure = !_obscure),
                child: Icon(
                  _obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  color: AppColors.primaryA45, size: 20,
                ),
              ),
              validator: (v) => (v == null || v.isEmpty) ? 'Please enter your password' : null,
            ),
            const SizedBox(height: 22),

            _buildSubmitButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboardType = TextInputType.text,
    bool obscureText = false,
    Widget? suffixIcon,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.primary),
      validator: validator,
      decoration: InputDecoration(
        filled: true,
        fillColor: AppColors.background,
        hintText: hint,
        hintStyle: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.primaryA35),
        prefixIcon: Padding(
          padding: const EdgeInsets.only(left: 16, right: 10),
          child: Icon(icon, color: AppColors.blue, size: 20),
        ),
        prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
        suffixIcon: suffixIcon != null
            ? Padding(padding: const EdgeInsets.only(right: 14), child: suffixIcon)
            : null,
        suffixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.lg), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.lg), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.25), width: 2),
        ),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.lg), borderSide: const BorderSide(color: Color(0xFFBA1A1A), width: 1.5)),
        focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.lg), borderSide: const BorderSide(color: Color(0xFFBA1A1A), width: 2)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        errorStyle: GoogleFonts.poppins(fontSize: 11),
      ),
    );
  }

  Widget _buildErrorBanner(String message) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFDAD6),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: const Color(0xFFBA1A1A).withValues(alpha: 0.15)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded, color: Color(0xFFBA1A1A), size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(message, style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.w600, color: const Color(0xFFBA1A1A))),
          ),
        ],
      ),
    );
  }

  Widget _buildSubmitButton() {
    return GestureDetector(
      onTapDown:  (_) => setState(() => _btnPressed = true),
      onTapUp:    (_) => setState(() => _btnPressed = false),
      onTapCancel: () => setState(() => _btnPressed = false),
      onTap: _isLoading ? null : _submit,
      child: AnimatedScale(
        scale: _btnPressed ? 0.965 : 1.0,
        duration: const Duration(milliseconds: 120),
        curve: Curves.easeOut,
        child: SizedBox(
          width: double.infinity,
          height: 54,
          child: ElevatedButton(
            onPressed: _isLoading ? null : _submit,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              disabledBackgroundColor: AppColors.primary.withValues(alpha: 0.75),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 0,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _isLoading ? 'Signing In...' : 'Sign In',
                  style: GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.white),
                ),
                _isLoading
                    ? const SizedBox(
                        width: 20, height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2.5, valueColor: AlwaysStoppedAnimation<Color>(Colors.white)),
                      )
                    : const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 22),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
