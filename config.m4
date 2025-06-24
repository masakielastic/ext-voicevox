PHP_ARG_ENABLE(voicevox, whether to enable VOICEVOX support,
[  --enable-voicevox       Enable VOICEVOX support])

if test "$PHP_VOICEVOX" != "no"; then
  PHP_NEW_EXTENSION(voicevox, voicevox.c voicevox_oop.c voicevox_compat.c, $ext_shared)
  
  dnl Check for dl library (required for dlopen/dlsym)
  AC_CHECK_LIB(dl, dlopen, [
    PHP_ADD_LIBRARY(dl, 1, VOICEVOX_SHARED_LIBADD)
  ], [
    AC_MSG_ERROR([libdl not found])
  ])
  
  dnl Add compiler flags
  PHP_SUBST(VOICEVOX_SHARED_LIBADD)
  
  dnl Define compile-time constants
  AC_DEFINE(HAVE_VOICEVOX, 1, [Whether you have VOICEVOX])
fi
