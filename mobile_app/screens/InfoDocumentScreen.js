import React from 'react';
import { ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ArrowLeft, FileText, ShieldCheck } from 'lucide-react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';

const DOCUMENTS = {
  privacy: {
    title: 'Aviso de privacidad',
    subtitle: 'Protección y uso responsable de tus datos',
    icon: ShieldCheck,
    sections: [
      ['Datos que utilizamos', 'ZeroWaste utiliza los datos necesarios para identificar tu cuenta, mostrar tu perfil, gestionar recolecciones, validar operaciones QR y mantener la seguridad del servicio.'],
      ['Finalidad', 'La información permite prestar las funciones solicitadas, prevenir abuso, conservar auditoría administrativa y comunicar actividad relevante de tu cuenta.'],
      ['Control de permisos', 'Puedes administrar ubicación, cámara, fotografías y notificaciones desde los ajustes del dispositivo. Desactivar un permiso limita únicamente las funciones que dependen de él.'],
      ['Seguridad', 'Los tokens de sesión se guardan en almacenamiento seguro del dispositivo. Las contraseñas, tokens QR y credenciales sensibles no se muestran ni se registran en la interfaz.'],
      ['Tus decisiones', 'Puedes actualizar tu perfil y preferencias desde la aplicación. Para una solicitud relacionada con tus datos, usa la sección Ayuda y soporte.'],
    ],
  },
  terms: {
    title: 'Términos de uso',
    subtitle: 'Condiciones para una comunidad segura',
    icon: FileText,
    sections: [
      ['Uso de la cuenta', 'La cuenta es personal. Mantén seguras tus credenciales y comunica cualquier acceso que no reconozcas.'],
      ['Comunidad', 'Publica contenido respetuoso, veraz y relacionado con sostenibilidad. La moderación puede retirar contenido dañino, engañoso o ajeno al propósito de ZeroWaste.'],
      ['Puntos y recompensas', 'Los puntos son determinados por las reglas activas del backend. No representan dinero, no son transferibles y los canjes dependen de disponibilidad y validación.'],
      ['Recolecciones y QR', 'Los horarios y la elegibilidad los decide el servidor. Los códigos de recolección son personales, temporales y de un solo uso.'],
      ['Disponibilidad', 'El servicio puede requerir mantenimiento. ZeroWaste conserva validaciones e idempotencia para evitar operaciones duplicadas cuando existe una interrupción.'],
    ],
  },
};

export default function InfoDocumentScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const document = DOCUMENTS[route.params?.document] || DOCUMENTS.terms;
  const Icon = document.icon;
  return <SafeAreaView className="flex-1 bg-slate-50" edges={['top','bottom']}><StatusBar style="dark" /><View className="flex-row items-center border-b border-slate-100 bg-white px-4 py-3"><TouchableOpacity onPress={()=>navigation.goBack()} className="h-11 w-11 items-center justify-center rounded-full bg-slate-100" accessibilityLabel="Volver"><ArrowLeft color="#0F172A" size={20}/></TouchableOpacity><View className="ml-4 flex-1"><Text className="text-xl font-black text-slate-950">{document.title}</Text><Text className="text-xs font-semibold text-slate-500">Información dentro de ZeroWaste</Text></View></View><ScrollView contentContainerStyle={{padding:20,paddingBottom:40}}><View className="rounded-[28px] bg-emerald-950 p-6"><View className="h-12 w-12 items-center justify-center rounded-full bg-white/10"><Icon color="#6EE7B7" size={23}/></View><Text className="mt-5 text-2xl font-black text-white">{document.subtitle}</Text><Text className="mt-2 leading-6 text-emerald-100" style={{textAlign:'justify'}}>Consulta esta información sin salir de la aplicación. El texto está diseñado para explicar de forma clara las reglas principales del servicio.</Text></View>{document.sections.map(([heading,body])=><View key={heading} className="mt-4 rounded-3xl border border-slate-100 bg-white p-5"><Text className="text-lg font-black text-emerald-950">{heading}</Text><Text className="mt-2 text-[15px] leading-6 text-slate-600" style={{textAlign:'justify'}}>{body}</Text></View>)}</ScrollView></SafeAreaView>;
}
