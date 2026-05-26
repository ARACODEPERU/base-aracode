export const healthServiceTypes = [
    { value: 'general', label: 'General', group: 'Medica', dental: false },
    { value: 'medicina_general', label: 'Medicina general', group: 'Medica', dental: false },
    { value: 'medicina_interna', label: 'Medicina interna', group: 'Medica', dental: false },
    { value: 'pediatria', label: 'Pediatría', group: 'Medica', dental: false },
    { value: 'ginecologia', label: 'Ginecología', group: 'Medica', dental: false },
    { value: 'cardiologia', label: 'Cardiología', group: 'Medica', dental: false },
    { value: 'dermatologia', label: 'Dermatología', group: 'Medica', dental: false },
    { value: 'traumatologia', label: 'Traumatología', group: 'Medica', dental: false },
    { value: 'neurologia', label: 'Neurología', group: 'Medica', dental: false },
    { value: 'oftalmologia', label: 'Oftalmología', group: 'Medica', dental: false },
    { value: 'otorrinolaringologia', label: 'Otorrinolaringología', group: 'Medica', dental: false },
    { value: 'gastroenterologia', label: 'Gastroenterología', group: 'Medica', dental: false },
    { value: 'endocrinologia', label: 'Endocrinología', group: 'Medica', dental: false },
    { value: 'urologia', label: 'Urología', group: 'Medica', dental: false },
    { value: 'psicologia', label: 'Psicología', group: 'Medica', dental: false },
    { value: 'nutricion', label: 'Nutrición', group: 'Medica', dental: false },
    { value: 'odontologia_general', label: 'Odontología general', group: 'Odontologica', dental: true },
    { value: 'ortodoncia', label: 'Ortodoncia', group: 'Odontologica', dental: true },
    { value: 'endodoncia', label: 'Endodoncia', group: 'Odontologica', dental: true },
    { value: 'periodoncia', label: 'Periodoncia', group: 'Odontologica', dental: true },
    { value: 'rehabilitacion_oral', label: 'Rehabilitación oral', group: 'Odontologica', dental: true },
    { value: 'cirugia_bucal', label: 'Cirugía bucal', group: 'Odontologica', dental: true },
    { value: 'odontopediatria', label: 'Odontopediatría', group: 'Odontologica', dental: true },
    { value: 'implantologia', label: 'Implantología', group: 'Odontologica', dental: true },
];

export const professionOptions = [
    'Médico cirujano',
    'Cirujano dentista',
    'Odontólogo',
    'Psicólogo',
    'Nutricionista',
    'Tecnólogo médico',
    'Enfermero',
];

export const serviceTypesByGroup = (group) => healthServiceTypes.filter((type) => type.group === group);

export const normalizeHealthServiceType = (serviceType) => serviceType === 'dental' ? 'odontologia_general' : serviceType;

export const serviceTypeOption = (serviceType) => {
    const normalizedServiceType = normalizeHealthServiceType(serviceType);

    return healthServiceTypes.find((type) => type.value === normalizedServiceType) || healthServiceTypes[0];
};

export const isDentalServiceType = (serviceType) => Boolean(serviceTypeOption(serviceType)?.dental);
