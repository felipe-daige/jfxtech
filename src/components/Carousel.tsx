import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Play, Pause, ChevronRight, ChevronLeft } from 'lucide-react';

const defaultSlides = [
  {
    id: 1,
    type: 'image',
    src: 'https://picsum.photos/seed/hero1/1920/1080',
    title: 'TITAN RTX 5090',
    subtitle: 'O NOVO PADRÃO DE PERFORMANCE',
    specs: ['24576 CUDA', '32GB GDDR7', '600W TGP']
  },
  {
    id: 2,
    type: 'video', // Reverted back to video style
    src: 'https://picsum.photos/seed/hero2/1920/1080',
    title: 'AERO-FLOW 120',
    subtitle: 'SILENCIOSO. PODEROSO. MAGNÉTICO.',
    specs: ['3000 RPM', '84 CFM', 'ROLAMENTO MAGLEV']
  },
  {
    id: 3,
    type: 'image',
    src: 'https://picsum.photos/seed/hero3/1920/1080',
    title: 'VISION 360Hz OLED',
    subtitle: 'CLAREZA INIGUALÁVEL A 360 QUADROS POR SEGUNDO',
    specs: ['RESPOSTA 0.03ms', 'QD-OLED', '1440p']
  }
];

export default function Carousel() {
  const [current, setCurrent] = useState(0);
  const [isPlaying, setIsPlaying] = useState(true);
  const slides = defaultSlides;

  useEffect(() => {
    if (!isPlaying) return;
    const timer = setInterval(() => {
      setCurrent((prev) => (prev + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, [isPlaying, slides.length]);

  const nextSlide = () => setCurrent((prev) => (prev + 1) % slides.length);
  const prevSlide = () => setCurrent((prev) => (prev - 1 + slides.length) % slides.length);

  return (
    <div className="relative h-[70vh] min-h-[600px] w-full bg-black overflow-hidden group">
      <AnimatePresence mode="wait">
        <motion.div
          key={current}
          initial={{ opacity: 0, scale: 1.05 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.8, ease: "easeInOut" }}
          className="absolute inset-0"
        >
          <img 
            src={slides[current].src} 
            alt={slides[current].title} 
            className="w-full h-full object-cover opacity-60"
            referrerPolicy="no-referrer"
          />
          {/* Technical Overlay Grid */}
          <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50 pointer-events-none"></div>
          
          {/* Content */}
          <div className="absolute inset-0 flex flex-col justify-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <motion.div 
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.3 }}
              className="max-w-2xl"
            >
              <div className="flex items-center gap-4 mb-4">
                <span className="text-xs font-mono tracking-widest text-[var(--color-lab-silver)] uppercase border border-gray-700 px-2 py-1">
                  {slides[current].type === 'video' ? 'DEMO AO VIVO' : 'DESTAQUE'}
                </span>
                <div className="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
              </div>
              
              <h1 className="text-5xl md:text-7xl font-bold tracking-tighter text-white mb-2 uppercase">
                {slides[current].title}
              </h1>
              <p className="text-lg md:text-xl text-gray-400 font-mono mb-8">
                {slides[current].subtitle}
              </p>

              <div className="flex gap-6 mb-10">
                {slides[current].specs.map((spec, idx) => (
                  <div key={idx} className="flex flex-col">
                    <span className="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_0{idx + 1}</span>
                    <span className="text-sm font-bold text-white uppercase">{spec}</span>
                  </div>
                ))}
              </div>

              <button className="bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors flex items-center gap-2 group/btn">
                EXPLORAR HARDWARE
                <ChevronRight className="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" />
              </button>
            </motion.div>
          </div>
        </motion.div>
      </AnimatePresence>

      {/* Controls */}
      <div className="absolute bottom-8 right-8 flex items-center gap-4 z-10">
        <button 
          onClick={() => setIsPlaying(!isPlaying)}
          className="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors"
        >
          {isPlaying ? <Pause className="w-4 h-4" /> : <Play className="w-4 h-4 ml-1" />}
        </button>
        
        <div className="flex gap-2">
          <button onClick={prevSlide} className="w-10 h-10 border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors">
            <ChevronLeft className="w-5 h-5" />
          </button>
          <button onClick={nextSlide} className="w-10 h-10 border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors">
            <ChevronRight className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Progress Indicators */}
      <div className="absolute bottom-8 left-8 flex gap-2 z-10">
        {slides.map((_, idx) => (
          <button 
            key={idx}
            onClick={() => setCurrent(idx)}
            className={`h-1 transition-all duration-300 ${current === idx ? 'w-12 bg-white' : 'w-4 bg-gray-600 hover:bg-gray-400'}`}
          />
        ))}
      </div>
    </div>
  );
}
