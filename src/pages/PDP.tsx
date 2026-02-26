import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { motion } from 'motion/react';
import { ChevronRight, Cpu, Zap, Shield, Battery, Crosshair } from 'lucide-react';

export default function PDP() {
  const { id } = useParams();
  const [activeTab, setActiveTab] = useState('OVERVIEW');

  // Mock product data
  const product = {
    name: "PRIME-X 8K WIRELESS",
    price: 149.99,
    description: "Engineered for absolute precision. The PRIME-X features our custom PAW3395 sensor and true 8000Hz wireless polling rate, delivering sub-millisecond latency in a 49g ultralight chassis.",
    images: [
      "https://picsum.photos/seed/mouse1/800/800",
      "https://picsum.photos/seed/mouse2/800/800",
      "https://picsum.photos/seed/mouse3/800/800"
    ],
    specs: {
      "SENSOR": "PAW3395 Custom",
      "POLLING RATE": "8000Hz Wireless",
      "WEIGHT": "49g",
      "SWITCHES": "Optical V3 (90M Clicks)",
      "BATTERY": "Up to 80 Hours",
      "MCU": "Nordic nRF52840"
    }
  };

  return (
    <motion.div 
      initial={{ opacity: 0 }} 
      animate={{ opacity: 1 }} 
      exit={{ opacity: 0 }}
      className="bg-[var(--color-lab-bg)]"
    >
      {/* Breadcrumbs */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav className="flex text-xs font-mono text-gray-500 uppercase tracking-widest">
          <Link to="/" className="hover:text-black">HOME</Link>
          <ChevronRight className="w-4 h-4 mx-2" />
          <Link to="/" className="hover:text-black">MICE</Link>
          <ChevronRight className="w-4 h-4 mx-2" />
          <span className="text-black">{product.name}</span>
        </nav>
      </div>

      {/* Product Hero */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
          {/* Image Gallery */}
          <div className="space-y-4">
            <div className="aspect-square bg-white rounded-xl border border-[var(--color-lab-border)] overflow-hidden flex items-center justify-center p-8">
              <img 
                src={product.images[0]} 
                alt={product.name} 
                className="w-full h-full object-contain mix-blend-multiply"
                referrerPolicy="no-referrer"
              />
            </div>
            <div className="grid grid-cols-3 gap-4">
              {product.images.map((img, idx) => (
                <button key={idx} className={`aspect-square bg-white rounded-lg border overflow-hidden p-2 ${idx === 0 ? 'border-black' : 'border-[var(--color-lab-border)]'}`}>
                  <img src={img} alt="" className="w-full h-full object-contain mix-blend-multiply" referrerPolicy="no-referrer" />
                </button>
              ))}
            </div>
          </div>

          {/* Product Info */}
          <div className="flex flex-col justify-center">
            <div className="mb-2 inline-block bg-black text-white text-[10px] font-mono px-2 py-1 uppercase tracking-widest rounded-sm">
              NEW RELEASE
            </div>
            <h1 className="text-4xl md:text-5xl font-bold tracking-tight mb-4">{product.name}</h1>
            <p className="text-2xl font-mono mb-6">${product.price}</p>
            <p className="text-gray-600 mb-8 leading-relaxed">
              {product.description}
            </p>

            {/* Quick Specs */}
            <div className="grid grid-cols-2 gap-4 mb-8">
              {Object.entries(product.specs).slice(0, 4).map(([key, value]) => (
                <div key={key} className="border border-[var(--color-lab-border)] bg-white p-4 rounded-lg">
                  <p className="text-[10px] font-mono text-gray-500 mb-1">{key}</p>
                  <p className="font-bold text-sm">{value}</p>
                </div>
              ))}
            </div>

            {/* Actions */}
            <div className="flex gap-4">
              <button className="flex-1 bg-black text-white py-4 px-8 font-bold tracking-widest hover:bg-gray-800 transition-colors uppercase text-sm">
                ADD TO CART
              </button>
              <button className="w-14 h-14 flex items-center justify-center border border-black hover:bg-gray-50 transition-colors">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Internal Anatomy Section */}
      <section className="bg-black text-white py-24 mt-12 relative overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-bold tracking-tight mb-4">INTERNAL ANATOMY</h2>
            <p className="text-gray-400 font-mono text-sm max-w-2xl mx-auto">
              EVERY COMPONENT METICULOUSLY ENGINEERED FOR MAXIMUM PERFORMANCE. NO COMPROMISES.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-12 items-center">
            {/* Left Specs */}
            <div className="space-y-12">
              <div className="flex flex-col items-end text-right">
                <div className="w-12 h-12 rounded-full border border-gray-700 flex items-center justify-center mb-4">
                  <Cpu className="w-5 h-5" />
                </div>
                <h3 className="font-bold text-lg mb-2">NORDIC MCU</h3>
                <p className="text-sm text-gray-400">High-performance ARM Cortex-M4 processor ensuring zero latency processing.</p>
              </div>
              <div className="flex flex-col items-end text-right">
                <div className="w-12 h-12 rounded-full border border-gray-700 flex items-center justify-center mb-4">
                  <Crosshair className="w-5 h-5" />
                </div>
                <h3 className="font-bold text-lg mb-2">PAW3395 SENSOR</h3>
                <p className="text-sm text-gray-400">Flawless tracking up to 26,000 DPI, 650 IPS, and 50G acceleration.</p>
              </div>
            </div>

            {/* Center Image (Exploded View Placeholder) */}
            <div className="relative aspect-[3/4] flex items-center justify-center">
              <div className="absolute inset-0 bg-gradient-to-b from-transparent via-gray-900/50 to-transparent rounded-full blur-3xl"></div>
              <img 
                src="https://picsum.photos/seed/exploded/600/800" 
                alt="Exploded View" 
                className="relative z-10 w-full object-contain mix-blend-screen opacity-80"
                referrerPolicy="no-referrer"
              />
              
              {/* Connection Lines (Decorative) */}
              <div className="absolute top-1/4 left-0 w-1/2 h-px bg-gradient-to-r from-transparent to-gray-500"></div>
              <div className="absolute top-2/3 right-0 w-1/2 h-px bg-gradient-to-l from-transparent to-gray-500"></div>
            </div>

            {/* Right Specs */}
            <div className="space-y-12">
              <div className="flex flex-col items-start text-left">
                <div className="w-12 h-12 rounded-full border border-gray-700 flex items-center justify-center mb-4">
                  <Zap className="w-5 h-5" />
                </div>
                <h3 className="font-bold text-lg mb-2">OPTICAL V3 SWITCHES</h3>
                <p className="text-sm text-gray-400">Light-speed actuation with zero debounce delay. Rated for 90M clicks.</p>
              </div>
              <div className="flex flex-col items-start text-left">
                <div className="w-12 h-12 rounded-full border border-gray-700 flex items-center justify-center mb-4">
                  <Battery className="w-5 h-5" />
                </div>
                <h3 className="font-bold text-lg mb-2">300mAh LIPO</h3>
                <p className="text-sm text-gray-400">Optimized power delivery for up to 80 hours of continuous 1000Hz gameplay.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Comparative Table */}
      <section className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div className="text-center mb-16">
          <h2 className="text-3xl font-bold tracking-tight mb-4">PERFORMANCE METRICS</h2>
          <p className="text-gray-500 font-mono text-sm">HOW WE STACK UP AGAINST THE INDUSTRY STANDARD.</p>
        </div>

        <div className="bg-white border border-[var(--color-lab-border)] rounded-xl overflow-hidden shadow-sm">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-[var(--color-lab-border)]">
                <th className="p-6 font-mono text-xs text-gray-500 uppercase tracking-widest w-1/3">SPECIFICATION</th>
                <th className="p-6 font-bold text-black border-l border-[var(--color-lab-border)] w-1/3 bg-white">PRIME-X 8K</th>
                <th className="p-6 font-mono text-xs text-gray-500 uppercase tracking-widest border-l border-[var(--color-lab-border)] w-1/3">INDUSTRY STD.</th>
              </tr>
            </thead>
            <tbody className="text-sm">
              <tr className="border-b border-[var(--color-lab-border)]">
                <td className="p-6 font-medium text-gray-900">Polling Rate</td>
                <td className="p-6 font-bold text-black border-l border-[var(--color-lab-border)] bg-gray-50/50">8000Hz (0.125ms)</td>
                <td className="p-6 text-gray-500 border-l border-[var(--color-lab-border)]">1000Hz (1ms)</td>
              </tr>
              <tr className="border-b border-[var(--color-lab-border)]">
                <td className="p-6 font-medium text-gray-900">Weight</td>
                <td className="p-6 font-bold text-black border-l border-[var(--color-lab-border)] bg-gray-50/50">49g</td>
                <td className="p-6 text-gray-500 border-l border-[var(--color-lab-border)]">60g - 70g</td>
              </tr>
              <tr className="border-b border-[var(--color-lab-border)]">
                <td className="p-6 font-medium text-gray-900">Switch Type</td>
                <td className="p-6 font-bold text-black border-l border-[var(--color-lab-border)] bg-gray-50/50">Optical V3 (Zero Debounce)</td>
                <td className="p-6 text-gray-500 border-l border-[var(--color-lab-border)]">Mechanical</td>
              </tr>
              <tr>
                <td className="p-6 font-medium text-gray-900">Sensor</td>
                <td className="p-6 font-bold text-black border-l border-[var(--color-lab-border)] bg-gray-50/50">PAW3395 Custom</td>
                <td className="p-6 text-gray-500 border-l border-[var(--color-lab-border)]">PAW3370</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </motion.div>
  );
}
